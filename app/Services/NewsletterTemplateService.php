<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Builds production-ready newsletter HTML straight from site statistics.
 *
 * Each template returns ['subject', 'body', 'summary', 'audience'] — the body
 * is full HTML (with inline styles) ready to drop into our email wrapper.
 * The wrapper itself lives in resources/views/emails/newsletter-blast.blade.php
 * and adds the unsubscribe footer / shell, so templates here only render the
 * content portion.
 *
 * Templates are *self-healing* — when the site is fresh and there's no real
 * data yet, they fall back to teaser copy instead of returning an empty list,
 * so the admin can still see what the email will look like.
 */
class NewsletterTemplateService
{
    /** Email-safe palette — keep in sync with the wrapper view. */
    private const COLOR_BG = '#0a0f0d';

    private const COLOR_CARD = '#111418';

    private const COLOR_BORDER = 'rgba(255,255,255,0.08)';

    private const COLOR_TEXT = '#e4e4e7';

    private const COLOR_MUTED = '#a1a1aa';

    private const COLOR_DIM = '#71717a';

    private const COLOR_ACCENT = '#34d399';

    private const COLOR_ACCENT_DARK = '#10b981';

    private const COLOR_AMBER = '#fbbf24';

    /**
     * Catalog of templates exposed to the admin UI.
     *
     * @return array<int,array{key:string,label:string,description:string,icon:string,group:string,is_dynamic:bool,default_audience:string}>
     */
    public function catalog(): array
    {
        return [
            ['key' => 'top_week', 'label' => 'Топ-моделі тижня', 'description' => 'П\'ятірка найпопулярніших за переглядами та завантаженнями за останні 7 днів.', 'icon' => 'flame', 'group' => 'highlights', 'is_dynamic' => true, 'default_audience' => 'all_subscribers'],
            ['key' => 'top_month', 'label' => 'Топ-моделі місяця', 'description' => 'Найзавантажуваніші моделі за останні 30 днів — для щомісячного дайджесту.', 'icon' => 'trophy', 'group' => 'highlights', 'is_dynamic' => true, 'default_audience' => 'all_subscribers'],
            ['key' => 'new_releases', 'label' => 'Нові надходження', 'description' => 'До 8 свіжоопублікованих моделей за тиждень з обкладинками й автором.', 'icon' => 'sparkles', 'group' => 'highlights', 'is_dynamic' => true, 'default_audience' => 'all_subscribers'],
            ['key' => 'free_picks', 'label' => 'Безкоштовні підбірки', 'description' => 'Кращі безкоштовні моделі — добре заходить для повторного залучення.', 'icon' => 'gift', 'group' => 'highlights', 'is_dynamic' => true, 'default_audience' => 'all_subscribers'],
            ['key' => 'category_spotlight', 'label' => 'Категорія тижня', 'description' => 'Найактивніша категорія + 4 топ-моделі з неї.', 'icon' => 'layers', 'group' => 'highlights', 'is_dynamic' => true, 'default_audience' => 'all_subscribers'],
            ['key' => 'bestsellers', 'label' => 'Хіти продажів', 'description' => 'Моделі з найбільшою кількістю оплачених замовлень за місяць.', 'icon' => 'crown', 'group' => 'highlights', 'is_dynamic' => true, 'default_audience' => 'buyers'],
            ['key' => 'top_authors', 'label' => 'Топ автори', 'description' => 'П\'ятірка найактивніших авторів — підсилюємо community.', 'icon' => 'users', 'group' => 'community', 'is_dynamic' => true, 'default_audience' => 'all_subscribers'],
            ['key' => 'digest_weekly', 'label' => 'Дайджест тижня', 'description' => 'Гібрид: топ-3 моделі + 3 нових + статистика тижня в одному листі.', 'icon' => 'newspaper', 'group' => 'community', 'is_dynamic' => true, 'default_audience' => 'all_subscribers'],
            ['key' => 'weekend_picks', 'label' => 'Підбірка на вихідні', 'description' => 'Камерна тематична добірка — 3 моделі для творчих вихідних.', 'icon' => 'coffee', 'group' => 'community', 'is_dynamic' => true, 'default_audience' => 'all_subscribers'],
            ['key' => 'authors_call', 'label' => 'Заклик авторам', 'description' => 'Лист авторам із метриками платформи й мотивацією публікувати.', 'icon' => 'megaphone', 'group' => 'community', 'is_dynamic' => true, 'default_audience' => 'authors'],
            ['key' => 'welcome', 'label' => 'Привітання нових', 'description' => 'Тепле welcome-повідомлення для нових підписників, без статистики.', 'icon' => 'heart', 'group' => 'system', 'is_dynamic' => false, 'default_audience' => 'all_subscribers'],
            ['key' => 'blank', 'label' => 'Порожній шаблон', 'description' => 'Каркас із header + footer без контенту — пишеш руками.', 'icon' => 'file', 'group' => 'system', 'is_dynamic' => false, 'default_audience' => 'all_subscribers'],
        ];
    }

    /**
     * Build a fully-rendered newsletter from a template key.
     *
     * @return array{subject:string,body:string,summary:string,audience:string,is_dynamic:bool,counts:array<string,int>}
     */
    public function compose(string $key): array
    {
        $meta = collect($this->catalog())->firstWhere('key', $key)
            ?? collect($this->catalog())->firstWhere('key', 'blank');

        $payload = match ($meta['key']) {
            'top_week' => $this->topPeriod(7, 'тижня', '🔥 Топ-моделі цього тижня', 'За тиждень ці моделі зібрали найбільше уваги. Обирай свою фавориту й друкуй на вихідних.'),
            'top_month' => $this->topPeriod(30, 'місяця', '🏆 Топ-моделі місяця', 'Підсумки місяця: ось що друкували найчастіше. Можливо, ти ще не бачив(ла) щось зі списку?'),
            'new_releases' => $this->newReleases(),
            'free_picks' => $this->freePicks(),
            'category_spotlight' => $this->categorySpotlight(),
            'bestsellers' => $this->bestsellers(),
            'top_authors' => $this->topAuthors(),
            'digest_weekly' => $this->digestWeekly(),
            'weekend_picks' => $this->weekendPicks(),
            'authors_call' => $this->authorsCall(),
            'welcome' => $this->welcome(),
            default => $this->blank(),
        };

        return array_merge($payload, [
            'audience' => $meta['default_audience'],
            'is_dynamic' => $meta['is_dynamic'],
            'counts' => $payload['counts'] ?? [],
        ]);
    }

    // ─── Templates ─────────────────────────────────────────────────────────

    private function topPeriod(int $days, string $word, string $subject, string $intro): array
    {
        $since = now()->subDays($days);

        $products = Product::published()
            ->where('published_at', '>=', $since->copy()->subYears(2)) // не обмежуємо жорстко
            ->with('author:id,name', 'category:id,slug,name')
            ->orderByDesc('downloads_count')
            ->orderByDesc('views_count')
            ->limit(5)
            ->get();

        if ($products->isEmpty()) {
            return $this->teaserFallback($subject, $intro);
        }

        $body = $this->shellHeader('Топ '.$word, $subject)
            .$this->shellIntro($intro)
            .$this->productGrid($products)
            .$this->ctaButton(route('products.index', ['sort' => 'popular']), '👀 Дивитися весь каталог')
            .$this->shellOutro('Маєш улюблену модель? Поділись враженнями в нашій спільноті — додай свій make.');

        return [
            'subject' => $subject,
            'body' => $body,
            'summary' => 'Топ-'.$products->count().' опублікованих моделей за переглядами/завантаженнями.',
            'counts' => ['products' => $products->count()],
        ];
    }

    private function newReleases(): array
    {
        $since = now()->subDays(7);

        $products = Product::published()
            ->where('published_at', '>=', $since)
            ->with('author:id,name')
            ->orderByDesc('published_at')
            ->limit(8)
            ->get();

        if ($products->isEmpty()) {
            return $this->teaserFallback('🆕 Свіжі моделі цього тижня',
                'Цього тижня автори ще не встигли поділитися новинками — але це чудовий момент завітати в каталог і знайти щось класичне.');
        }

        $subject = '🆕 Свіжі моделі: '.$products->count().' нових цього тижня';

        $body = $this->shellHeader('Новинки', $subject)
            .$this->shellIntro('Автори опублікували '.$products->count().' нових моделей за останні 7 днів. Ось що варто роздрукувати першим:')
            .$this->productGrid($products, columns: 2)
            .$this->ctaButton(route('products.index', ['sort' => 'newest']), 'Усі новинки →')
            .$this->shellOutro('Підпишись на улюблених авторів — і отримуй сповіщення відразу після публікації.');

        return [
            'subject' => $subject,
            'body' => $body,
            'summary' => $products->count().' нових моделей за тиждень.',
            'counts' => ['products' => $products->count()],
        ];
    }

    private function freePicks(): array
    {
        $products = Product::published()
            ->where('is_free', true)
            ->with('author:id,name')
            ->orderByDesc('downloads_count')
            ->orderByDesc('views_count')
            ->limit(6)
            ->get();

        if ($products->isEmpty()) {
            return $this->teaserFallback('🎁 Безкоштовно цього тижня',
                'Зазирни на 3Dify — серед платних моделей завжди знайдеться кілька якісних безкоштовних.');
        }

        $subject = '🎁 Безкоштовні 3D-моделі — '.$products->count().' на завантаження';

        $body = $this->shellHeader('Безкоштовно', $subject)
            .$this->shellIntro('Жодних платежів — просто завантажуй і друкуй. Ось добірка найкращих безкоштовних моделей за весь час:')
            .$this->productGrid($products, columns: 2)
            .$this->ctaButton(route('products.index', ['free' => 1]), 'Усі безкоштовні моделі')
            .$this->shellOutro('Подобається конкретний автор? Підтримай його чайовими прямо на сторінці моделі.');

        return [
            'subject' => $subject,
            'body' => $body,
            'summary' => $products->count().' безкоштовних моделей.',
            'counts' => ['products' => $products->count()],
        ];
    }

    private function categorySpotlight(): array
    {
        $category = Category::query()
            ->withCount(['products' => fn ($q) => $q->where('status', 'published')])
            ->orderByDesc('products_count')
            ->first();

        if (! $category || $category->products_count === 0) {
            return $this->teaserFallback('💡 Категорія тижня',
                'Поки в каталозі замало моделей для повноцінного огляду категорії. Загляни пізніше — або стань першим автором у своїй ніші.');
        }

        $products = Product::published()
            ->where('category_id', $category->id)
            ->with('author:id,name')
            ->orderByDesc('views_count')
            ->limit(4)
            ->get();

        $catName = is_array($category->name) ? ($category->name['uk'] ?? $category->name['en'] ?? '—') : $category->name;
        $subject = '💡 У фокусі: '.$catName;

        $body = $this->shellHeader('Категорія тижня', $subject)
            .$this->shellIntro('Цього тижня в центрі уваги — '.e($catName).'. У каталозі '.$category->products_count.' моделей у цій категорії. Ось чотири, з яких варто почати:')
            .$this->productGrid($products, columns: 2)
            .$this->ctaButton(route('categories.show', $category), 'Переглянути всю категорію')
            .$this->shellOutro('Шукаєш щось специфічне? Напиши нам у відповідь — підкажемо.');

        return [
            'subject' => $subject,
            'body' => $body,
            'summary' => 'Категорія "'.$catName.'" — '.$products->count().' топ-моделей.',
            'counts' => ['products' => $products->count(), 'category_total' => (int) $category->products_count],
        ];
    }

    private function bestsellers(): array
    {
        if (! Schema::hasTable('order_items') || ! Schema::hasTable('orders')) {
            return $this->teaserFallback('💰 Хіти продажів',
                'Модуль продажів ще не активний. Якщо плануєш продавати моделі — налаштуй платіжний шлюз у /admin/content?tab=payments.');
        }

        $since = now()->subDays(30);

        $top = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNotNull('orders.paid_at')
            ->where('orders.paid_at', '>=', $since)
            ->select('order_items.product_id', DB::raw('COUNT(*) as units'))
            ->groupBy('order_items.product_id')
            ->orderByDesc('units')
            ->limit(5)
            ->get();

        if ($top->isEmpty()) {
            return $this->teaserFallback('💰 Хіти продажів',
                'За останній місяць ще не накопичилось достатньо платних замовлень для повноцінного рейтингу. Поверни цей лист пізніше або обери інший шаблон.');
        }

        $products = Product::with('author:id,name')->whereIn('id', $top->pluck('product_id'))->get()->keyBy('id');
        $ordered = $top->map(fn ($row) => $products->get($row->product_id))->filter();

        $subject = '👑 Хіти продажів місяця';

        $body = $this->shellHeader('Бестселери', $subject)
            .$this->shellIntro('Ось які моделі цього місяця знайшли найбільше нових власників. Якщо вагаєшся, що друкувати наступним — починай із цього списку:')
            .$this->productGrid($ordered, columns: 1, showPrice: true)
            .$this->ctaButton(route('products.index', ['sort' => 'popular']), 'Більше популярного');

        return [
            'subject' => $subject,
            'body' => $body,
            'summary' => 'Топ-'.$ordered->count().' моделей за продажами останніх 30 днів.',
            'counts' => ['products' => $ordered->count()],
        ];
    }

    private function topAuthors(): array
    {
        $authors = User::query()
            ->whereHas('products', fn ($q) => $q->where('status', 'published'))
            ->withCount(['products as published_count' => fn ($q) => $q->where('status', 'published')])
            ->orderByDesc('published_count')
            ->limit(5)
            ->get();

        if ($authors->isEmpty()) {
            return $this->teaserFallback('🌟 Зустрічайте топ-авторів',
                'У спільноті ще не з\'явилось публікацій. Можливо, саме твій момент стати першим — заглядай в /author/dashboard.');
        }

        $subject = '🌟 Зустрічайте топ-авторів 3Dify';

        $rows = '';
        foreach ($authors as $i => $author) {
            $rows .= $this->authorRow($author, $i + 1);
        }

        $body = $this->shellHeader('Спільнота', $subject)
            .$this->shellIntro('Ці автори зробили 3Dify тим, чим він є. Підпишись, щоб не пропустити їхні нові моделі:')
            .$this->card($rows)
            .$this->ctaButton(route('products.index', ['sort' => 'newest']), 'Дивитися всі моделі');

        return [
            'subject' => $subject,
            'body' => $body,
            'summary' => 'Топ-'.$authors->count().' авторів за кількістю публікацій.',
            'counts' => ['authors' => $authors->count()],
        ];
    }

    private function digestWeekly(): array
    {
        $since = now()->subDays(7);

        $top = Product::published()
            ->orderByDesc('views_count')
            ->limit(3)
            ->with('author:id,name')
            ->get();

        $fresh = Product::published()
            ->where('published_at', '>=', $since)
            ->with('author:id,name')
            ->latest('published_at')
            ->limit(3)
            ->get();

        $stats = [
            'new_products' => Product::where('status', 'published')->where('published_at', '>=', $since)->count(),
            'new_users' => Schema::hasTable('users') ? User::where('created_at', '>=', $since)->count() : 0,
            'new_makes' => Schema::hasTable('product_makes') ? DB::table('product_makes')->where('created_at', '>=', $since)->count() : 0,
        ];

        if ($top->isEmpty() && $fresh->isEmpty()) {
            return $this->teaserFallback('📰 Дайджест тижня',
                'Це твій перший тиждень із 3Dify? Ласкаво просимо! Завітай у каталог і знайди свою першу модель.');
        }

        $subject = '📰 Дайджест тижня · 3Dify';

        $body = $this->shellHeader('Тижневий дайджест', 'Що сталося в 3Dify за тиждень');

        $body .= $this->statsRow($stats);

        if ($top->isNotEmpty()) {
            $body .= $this->sectionHeader('🔥 Найпопулярніше');
            $body .= $this->productGrid($top, columns: 1);
        }

        if ($fresh->isNotEmpty()) {
            $body .= $this->sectionHeader('🆕 Свіжі надходження');
            $body .= $this->productGrid($fresh, columns: 1);
        }

        $body .= $this->ctaButton(route('products.index'), 'Переглянути весь каталог');
        $body .= $this->shellOutro('Цей дайджест приходить раз на тиждень. Можна налаштувати частоту в профілі.');

        return [
            'subject' => $subject,
            'body' => $body,
            'summary' => 'Дайджест: '.$top->count().' топ + '.$fresh->count().' свіжих + статистика.',
            'counts' => ['products_top' => $top->count(), 'products_fresh' => $fresh->count(), 'new_users' => $stats['new_users']],
        ];
    }

    private function weekendPicks(): array
    {
        $picks = Product::published()
            ->inRandomOrder()
            ->with('author:id,name')
            ->limit(3)
            ->get();

        if ($picks->isEmpty()) {
            return $this->teaserFallback('☕ Підбірка на вихідні',
                'Каталог поки невеликий. Заглянь у /models — і обери щось для творчих вихідних.');
        }

        $subject = '☕ Кураторська підбірка на вихідні';

        $body = $this->shellHeader('Вихідні з 3Dify', $subject)
            .$this->shellIntro('Ми зібрали три моделі, які чудово підходять для повільної суботи з ранковою кавою та принтером.')
            .$this->productGrid($picks, columns: 1, showPrice: true)
            .$this->ctaButton(route('products.index'), 'Більше моделей у каталозі')
            .$this->shellOutro('Класних вихідних! Не забудь поділитися фото готового друку — теги в Instagram: #3dify');

        return [
            'subject' => $subject,
            'body' => $body,
            'summary' => 'Випадкова підбірка з 3 опублікованих моделей.',
            'counts' => ['products' => $picks->count()],
        ];
    }

    private function authorsCall(): array
    {
        $stats = [
            'authors' => Schema::hasTable('users') ? User::where('role', 'author')->count() : 0,
            'products' => Schema::hasTable('products') ? Product::where('status', 'published')->count() : 0,
            'subscribers' => Schema::hasTable('newsletter_subscribers') ? DB::table('newsletter_subscribers')->whereNull('unsubscribed_at')->count() : 0,
        ];

        $subject = '📣 Стань автором 3Dify — у нас вже '.$stats['products'].' моделей';

        $body = $this->shellHeader('Для авторів', $subject)
            .$this->shellIntro('Якщо ти моделюєш у Blender, Fusion 360 чи Solidworks — твоя робота може приносити дохід. На 3Dify вже:')
            .$this->statsRow([
                'authors' => $stats['authors'],
                'products' => $stats['products'],
                'subscribers' => $stats['subscribers'],
            ], labels: ['authors' => 'авторів', 'products' => 'опубліковано', 'subscribers' => 'у спільноті'])
            .$this->shellIntro('Платформа бере мінімальну комісію, виплати — раз на тиждень, і ти повністю контролюєш свої моделі.')
            .$this->ctaButton(route('register'), 'Опублікувати першу модель')
            .$this->shellOutro('Питання щодо публікації? Просто відповідай на цей лист — ми читаємо кожне.');

        return [
            'subject' => $subject,
            'body' => $body,
            'summary' => 'Лист авторам зі статистикою платформи.',
            'counts' => $stats,
        ];
    }

    private function welcome(): array
    {
        $subject = '👋 Ласкаво просимо до спільноти 3Dify';

        $body = $this->shellHeader('Welcome', $subject)
            .$this->shellIntro('Радіємо тебе бачити! Ти підписав(ла)ся на оновлення 3Dify — раз на тиждень ми надсилатимемо найкращі моделі, новини та підбірки.')
            .$this->card(
                '<p style="margin:0 0 12px;color:'.self::COLOR_TEXT.';font-size:14px;line-height:1.6;font-weight:600;">Ось що варто зробити прямо зараз:</p>'
                .'<ul style="margin:0 0 0 18px;padding:0;color:'.self::COLOR_MUTED.';font-size:14px;line-height:1.8;">'
                .'<li>Додай улюблених авторів у підписки — будеш першим бачити новинки.</li>'
                .'<li>Зайди в каталог: '.$this->inlineLink(route('products.index'), 'каталог моделей').'.</li>'
                .'<li>Якщо моделюєш сам(а) — публікуй! '.$this->inlineLink(route('register'), 'Стати автором').'.</li>'
                .'</ul>'
            )
            .$this->ctaButton(route('products.index'), 'Перейти у каталог')
            .$this->shellOutro('Якщо в тебе є питання — просто відповідай на цей лист. Це справжня людина.');

        return [
            'subject' => $subject,
            'body' => $body,
            'summary' => 'Привітальний лист для нових підписників.',
            'counts' => [],
        ];
    }

    private function blank(): array
    {
        $body = $this->shellHeader('3Dify', 'Тема листа з\'явиться тут')
            .$this->shellIntro('Сюди впиши вступний абзац: про що цей лист і чому варто читати далі.')
            .$this->card('<p style="margin:0;color:'.self::COLOR_MUTED.';font-size:14px;line-height:1.6;">Основний контент — параграфи, списки, картинки. Можна вставити свій HTML.</p>')
            .$this->ctaButton(route('products.index'), 'Кнопка дії');

        return [
            'subject' => '3Dify — заголовок листа',
            'body' => $body,
            'summary' => 'Каркас без статистики — пиши руками.',
            'counts' => [],
        ];
    }

    // ─── HTML building blocks ─────────────────────────────────────────────

    private function teaserFallback(string $subject, string $intro): array
    {
        $body = $this->shellHeader('3Dify', $subject)
            .$this->shellIntro($intro)
            .$this->ctaButton(route('products.index'), 'У каталог →');

        return [
            'subject' => $subject,
            'body' => $body,
            'summary' => 'Замало даних для повноцінного шаблону — залишено teaser.',
            'counts' => [],
        ];
    }

    private function shellHeader(string $eyebrow, string $title): string
    {
        $accent = self::COLOR_ACCENT;
        $border = self::COLOR_BORDER;

        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 24px;">
            <tr><td style="padding:0 0 6px;">
                <span style="display:inline-block;padding:4px 10px;border:1px solid '.$accent.';border-radius:999px;color:'.$accent.';font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.16em;">'.e($eyebrow).'</span>
            </td></tr>
            <tr><td style="padding:8px 0 16px;">
                <h1 style="margin:0;font-size:26px;font-weight:800;line-height:1.2;color:#ffffff;">'.e($title).'</h1>
            </td></tr>
            <tr><td style="border-top:1px solid '.$border.';padding:0;font-size:0;line-height:0;">&nbsp;</td></tr>
        </table>';
    }

    private function shellIntro(string $text): string
    {
        return '<p style="margin:0 0 20px;color:'.self::COLOR_TEXT.';font-size:15px;line-height:1.65;">'.e($text).'</p>';
    }

    private function shellOutro(string $text): string
    {
        return '<p style="margin:24px 0 0;color:'.self::COLOR_DIM.';font-size:13px;line-height:1.6;font-style:italic;">'.e($text).'</p>';
    }

    private function sectionHeader(string $label): string
    {
        return '<h2 style="margin:28px 0 12px;color:#ffffff;font-size:16px;font-weight:700;letter-spacing:0.02em;">'.e($label).'</h2>';
    }

    private function card(string $innerHtml): string
    {
        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px;border:1px solid '.self::COLOR_BORDER.';border-radius:14px;background:rgba(255,255,255,0.03);">
            <tr><td style="padding:16px 18px;">'.$innerHtml.'</td></tr>
        </table>';
    }

    private function ctaButton(string $url, string $label): string
    {
        $a = self::COLOR_ACCENT;
        $b = self::COLOR_ACCENT_DARK;

        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0 0;"><tr><td align="center">
            <a href="'.e($url).'" style="display:inline-block;padding:14px 28px;background:linear-gradient(135deg,'.$a.','.$b.');color:#0a0f0d;font-weight:700;font-size:14px;text-decoration:none;border-radius:12px;letter-spacing:0.02em;">'.e($label).'</a>
        </td></tr></table>';
    }

    private function inlineLink(string $url, string $label): string
    {
        return '<a href="'.e($url).'" style="color:'.self::COLOR_ACCENT.';text-decoration:underline;font-weight:600;">'.e($label).'</a>';
    }

    /**
     * @param  Collection<int,Product>  $products
     */
    private function productGrid(Collection $products, int $columns = 2, bool $showPrice = false): string
    {
        if ($products->isEmpty()) {
            return '';
        }

        if ($columns === 1) {
            $rows = '';
            foreach ($products as $p) {
                $rows .= $this->productRowFull($p, $showPrice);
            }

            return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 8px;">'.$rows.'</table>';
        }

        // 2 cards per row using a table to survive Outlook
        $cells = '';
        $i = 0;
        $row = '';
        foreach ($products as $p) {
            $row .= '<td valign="top" width="50%" style="padding:6px;">'.$this->productCardSmall($p, $showPrice).'</td>';
            $i++;
            if ($i % 2 === 0) {
                $cells .= '<tr>'.$row.'</tr>';
                $row = '';
            }
        }
        if ($row !== '') {
            // pad odd row with empty cell
            $row .= '<td width="50%" style="padding:6px;">&nbsp;</td>';
            $cells .= '<tr>'.$row.'</tr>';
        }

        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 8px;border-collapse:separate;">'.$cells.'</table>';
    }

    private function productCardSmall(Product $p, bool $showPrice): string
    {
        $url = route('products.show', $p);
        $title = e($p->localized('title'));
        $author = e($p->author?->name ?? '—');
        $img = $this->coverUrl($p);
        $price = $p->is_free ? 'Безкоштовно' : number_format((float) $p->price, 2).' '.$p->currency;
        $priceColor = $p->is_free ? self::COLOR_ACCENT : self::COLOR_AMBER;

        $imgBlock = $img
            ? '<a href="'.$url.'" style="display:block;"><img src="'.$img.'" alt="'.$title.'" width="260" style="display:block;width:100%;max-width:100%;height:auto;border-radius:10px 10px 0 0;border:0;outline:none;text-decoration:none;"></a>'
            : '<div style="height:140px;background:linear-gradient(135deg,rgba(52,211,153,0.15),rgba(125,211,252,0.1));border-radius:10px 10px 0 0;"></div>';

        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid '.self::COLOR_BORDER.';border-radius:10px;background:'.self::COLOR_CARD.';">
            <tr><td style="padding:0;">'.$imgBlock.'</td></tr>
            <tr><td style="padding:12px 14px 14px;">
                <a href="'.$url.'" style="text-decoration:none;color:#ffffff;font-weight:700;font-size:14px;line-height:1.35;display:block;margin-bottom:6px;">'.$title.'</a>
                <div style="color:'.self::COLOR_DIM.';font-size:11px;line-height:1.4;">'.$author.'</div>
                '.($showPrice ? '<div style="margin-top:8px;color:'.$priceColor.';font-weight:700;font-size:13px;">'.e($price).'</div>' : '').'
            </td></tr>
        </table>';
    }

    private function productRowFull(Product $p, bool $showPrice): string
    {
        $url = route('products.show', $p);
        $title = e($p->localized('title'));
        $author = e($p->author?->name ?? '—');
        $img = $this->coverUrl($p);
        $views = number_format((int) $p->views_count, 0, '.', ' ');
        $downloads = number_format((int) $p->downloads_count, 0, '.', ' ');
        $price = $p->is_free ? 'Безкоштовно' : number_format((float) $p->price, 2).' '.$p->currency;

        $thumb = $img
            ? '<img src="'.$img.'" alt="'.$title.'" width="120" style="display:block;width:120px;height:90px;border-radius:8px;object-fit:cover;border:0;">'
            : '<div style="width:120px;height:90px;border-radius:8px;background:linear-gradient(135deg,rgba(52,211,153,0.15),rgba(125,211,252,0.1));"></div>';

        return '<tr><td style="padding:8px 0;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid '.self::COLOR_BORDER.';border-radius:12px;background:'.self::COLOR_CARD.';">
                <tr>
                    <td valign="top" width="120" style="padding:12px 0 12px 12px;">
                        <a href="'.$url.'">'.$thumb.'</a>
                    </td>
                    <td valign="top" style="padding:14px 16px;">
                        <a href="'.$url.'" style="text-decoration:none;color:#ffffff;font-weight:700;font-size:15px;line-height:1.3;display:block;margin-bottom:4px;">'.$title.'</a>
                        <div style="color:'.self::COLOR_DIM.';font-size:11px;margin-bottom:8px;">'.$author.'</div>
                        <div style="color:'.self::COLOR_MUTED.';font-size:12px;">
                            <span style="margin-right:14px;">👁 '.$views.'</span>
                            <span>⬇ '.$downloads.'</span>
                            '.($showPrice ? '<span style="float:right;color:'.($p->is_free ? self::COLOR_ACCENT : self::COLOR_AMBER).';font-weight:700;">'.e($price).'</span>' : '').'
                        </div>
                    </td>
                </tr>
            </table>
        </td></tr>';
    }

    private function authorRow(User $u, int $rank): string
    {
        $url = route('authors.show', $u);
        $name = e($u->name);
        $count = (int) ($u->published_count ?? 0);
        $word = $this->plural($count, ['модель', 'моделі', 'моделей']);

        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 10px;">
            <tr>
                <td width="32" valign="middle" style="padding-right:12px;">
                    <div style="width:32px;height:32px;border-radius:8px;background:'.self::COLOR_ACCENT.';color:#0a0f0d;font-weight:800;font-size:13px;text-align:center;line-height:32px;">'.$rank.'</div>
                </td>
                <td valign="middle">
                    <a href="'.$url.'" style="color:#ffffff;text-decoration:none;font-weight:700;font-size:14px;">'.$name.'</a>
                    <div style="color:'.self::COLOR_DIM.';font-size:11px;margin-top:2px;">'.$count.' '.$word.'</div>
                </td>
            </tr>
        </table>';
    }

    /**
     * @param  array<string,int>  $stats
     * @param  array<string,string>  $labels
     */
    private function statsRow(array $stats, array $labels = []): string
    {
        $defaultLabels = [
            'new_products' => 'нових моделей',
            'new_users' => 'нових користувачів',
            'new_makes' => 'нових принтів',
            'authors' => 'активних авторів',
            'products' => 'опубліковано',
            'subscribers' => 'підписників',
        ];
        $labels = array_merge($defaultLabels, $labels);

        $cells = '';
        foreach ($stats as $key => $value) {
            $label = $labels[$key] ?? $key;
            $cells .= '<td valign="top" width="33%" style="padding:6px;">
                <div style="border:1px solid '.self::COLOR_BORDER.';border-radius:12px;background:'.self::COLOR_CARD.';padding:14px;text-align:center;">
                    <div style="font-size:24px;font-weight:800;color:'.self::COLOR_ACCENT.';line-height:1;">'.number_format((int) $value, 0, '.', ' ').'</div>
                    <div style="font-size:10px;color:'.self::COLOR_DIM.';text-transform:uppercase;letter-spacing:0.12em;margin-top:6px;font-weight:700;">'.e($label).'</div>
                </div>
            </td>';
        }

        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px;border-collapse:separate;"><tr>'.$cells.'</tr></table>';
    }

    private function coverUrl(Product $p): ?string
    {
        if (! $p->cover_path) {
            return null;
        }
        try {
            if (Storage::disk('public')->exists($p->cover_path)) {
                $url = Storage::disk('public')->url($p->cover_path);

                // Email clients need an absolute URL
                if (str_starts_with($url, '/')) {
                    $url = rtrim(config('app.url'), '/').$url;
                }

                return $url;
            }
        } catch (\Throwable) {
            // ignore
        }

        return null;
    }

    /**
     * @param  array{0:string,1:string,2:string}  $forms  ['1', '2..4', '5+']
     */
    private function plural(int $count, array $forms): string
    {
        $mod10 = $count % 10;
        $mod100 = $count % 100;

        if ($mod10 === 1 && $mod100 !== 11) {
            return $forms[0];
        }
        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 10 || $mod100 >= 20)) {
            return $forms[1];
        }

        return $forms[2];
    }
}
