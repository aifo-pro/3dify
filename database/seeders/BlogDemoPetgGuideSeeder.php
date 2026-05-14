<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use App\Models\User;
use App\Services\BlogContentSanitizer;
use App\Services\BlogPostBlockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class BlogDemoPetgGuideSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('blog_posts') || ! Schema::hasTable('blog_post_blocks')) {
            $this->command?->warn('Blog tables missing; run migrations first.');

            return;
        }

        $slug = 'drukuvaty-petg-bez-problem';

        $user = User::query()
            ->whereIn('role', ['admin', 'author'])
            ->orderByRaw('CASE WHEN role = \'admin\' THEN 0 ELSE 1 END')
            ->first() ?? User::query()->first();

        $blocks = [
            ['type' => 'heading', 'is_active' => true, 'data' => [
                'level' => 2,
                'title_uk' => 'PETG на практиці: що важливо знати',
                'title_en' => 'PETG in practice: what matters',
                'anchor' => 'petg-practical',
            ]],
            ['type' => 'paragraph', 'is_active' => true, 'data' => [
                'text_uk' => '<p>PETG поєднує міцність і термостійкість краще за PLA, але менш примхливий за ABS. Ключ — стабільна температура сопла, правильний зазор і повільніший перший шар.</p><p><strong>Коротко:</strong> сушіть філамент, не перегрівайте стол і не ганяйте вентилятор на першому шарі.</p>',
                'text_en' => '<p>PETG blends toughness and heat resistance better than PLA, with fewer headaches than ABS. The key is stable nozzle temperature, correct Z offset, and a slower first layer.</p><p><strong>In short:</strong> dry filament, avoid overheating the bed, and keep the part fan low on layer one.</p>',
            ]],
            ['type' => 'table', 'is_active' => true, 'data' => [
                'title_uk' => 'Стартові параметри (орієнтир)',
                'title_en' => 'Starting parameters (reference)',
                'headers' => ['Параметр / Parameter', 'UK / EN'],
                'rows' => [
                    ['Сопло / Nozzle', '240–250 °C'],
                    ['Стіл / Bed', '75–85 °C'],
                    ['Вентилятор / Fan', '20–40% після 3-го шару'],
                    ['Швидкість / Speed', '40–80 mm/s'],
                ],
            ]],
            ['type' => 'tips', 'is_active' => true, 'data' => [
                'title_uk' => 'Поради для чистої поверхні',
                'title_en' => 'Tips for a clean surface',
                'icon' => '✓',
                'items_uk' => [
                    'Зменшіть зазор на 0,02–0,05 мм відносно PLA — PETG любить «трохи ближче», але без задирів.',
                    'Використовуйте клей-розділювач на PEI, щоб уникнути «зварювання» першого шару.',
                ],
                'items_en' => [
                    'Reduce Z offset slightly vs PLA — PETG likes a touch closer without digging in.',
                    'Use glue stick / separator on PEI to avoid first-layer welding.',
                ],
            ]],
            ['type' => 'warning', 'is_active' => true, 'data' => [
                'title_uk' => 'Попередження',
                'title_en' => 'Warning',
                'tone' => 'amber',
                'text_uk' => '<p>Занадто низький зазор + перегрітий стіл можуть зірвати покриття PEI. Завжди контролюйте перший шар вручну.</p>',
                'text_en' => '<p>Too low Z offset plus an overheated bed can damage PEI. Always babysit the first layer.</p>',
            ]],
            ['type' => 'steps', 'is_active' => true, 'data' => [
                'title_uk' => 'Кроки перед друком',
                'title_en' => 'Steps before printing',
                'steps' => [
                    [
                        'title_uk' => 'Підсушіть філамент',
                        'title_en' => 'Dry the filament',
                        'text_uk' => '<p>4–6 годин при 60–65 °C для катушки — менше ниток, менше слабких шарів.</p>',
                        'text_en' => '<p>4–6 hours at 60–65 °C on the spool — fewer strings, stronger layers.</p>',
                    ],
                    [
                        'title_uk' => 'Калібруйте flow і ретракт',
                        'title_en' => 'Calibrate flow and retraction',
                        'text_uk' => '<p>Один тестовий баштик краще за десяток «на око» — зафіксуйте значення в профілі слайсера.</p>',
                        'text_en' => '<p>One tower test beats guessing — save values in your slicer profile.</p>',
                    ],
                    [
                        'title_uk' => 'Перший шар повільно',
                        'title_en' => 'Slow first layer',
                        'text_uk' => '<p>15–25 mm/s, мінімальний вентилятор — краща адгезія без підйому кутів.</p>',
                        'text_en' => '<p>15–25 mm/s, minimal fan — better adhesion without lifted corners.</p>',
                    ],
                ],
            ]],
            ['type' => 'image_text', 'is_active' => true, 'data' => [
                'path' => '',
                'title_uk' => 'Зона друку без протягів',
                'title_en' => 'A draft-free print zone',
                'text_uk' => '<p>Різкі перепади температури навколо моделі провокують розшарування. Закрийте корпус або зменшіть струмені від вентиляції кімнати.</p>',
                'text_en' => '<p>Sharp air swings around the part encourage layer splits. Use an enclosure or reduce room drafts.</p>',
                'image_position' => 'left',
            ]],
            ['type' => 'faq', 'is_active' => true, 'data' => [
                'items' => [
                    [
                        'question_uk' => 'Чому PETG «рябить» на верхах?',
                        'question_en' => 'Why does PETG look rough on tops?',
                        'answer_uk' => '<p>Зазвичай недостатньо охолодження або завеликий шар. Підніміть вентилятор після 3-го шару та зменшіть ширину екструзії для верхів.</p>',
                        'answer_en' => '<p>Usually cooling or layer width. Raise fan after layer 3 and tune top layer extrusion width.</p>',
                    ],
                    [
                        'question_uk' => 'Чи потрібен сухий бокс?',
                        'question_en' => 'Do I need a dry box?',
                        'answer_uk' => '<p>Так, PETG активно вбирає вологу; нитки та слабкі шари — типовий симптом.</p>',
                        'answer_en' => '<p>Yes — PETG absorbs moisture; stringing and weak layers are common symptoms.</p>',
                    ],
                    [
                        'question_uk' => 'PLA-профіль можна копіювати?',
                        'question_en' => 'Can I copy a PLA profile?',
                        'answer_uk' => '<p>Лише як стартову точку: змініть температури, ретракт і вентилятор першого шару.</p>',
                        'answer_en' => '<p>Only as a starting point — change temps, retraction, and first-layer fan.</p>',
                    ],
                ],
            ]],
            ['type' => 'cta', 'is_active' => true, 'data' => [
                'title_uk' => 'Шукайте моделі під ваш філамент',
                'title_en' => 'Find models for your filament',
                'text_uk' => '<p>У каталозі 3Dify — готові STL/3MF та описи під різні матеріали.</p>',
                'text_en' => '<p>Browse STL/3MF listings tuned for different materials.</p>',
                'button_text_uk' => 'Перейти до каталогу',
                'button_text_en' => 'Browse catalog',
                'button_url' => url('/products'),
            ]],
        ];

        $post = BlogPost::updateOrCreate(
            ['slug' => $slug],
            [
                'user_id' => $user?->id,
                'title_uk' => 'Як друкувати PETG без проблем: повний гайд для 3D-друку',
                'title_en' => 'How to print PETG without issues: a complete 3D printing guide',
                'excerpt_uk' => 'Температури, стіл, ретракт і перший шар — усе, щоб PETG лягав рівно без ниток і розшарувань.',
                'excerpt_en' => 'Temperatures, bed, retraction, and the first layer — everything to print PETG cleanly without stringing or splits.',
                'seo_title_uk' => 'PETG 3D-друк: гайд від 3Dify',
                'seo_title_en' => 'PETG 3D printing guide | 3Dify',
                'seo_description_uk' => 'Практичні налаштування PETG: сопло, стіл, вентилятор, кроки перед друком і відповіді на часті питання.',
                'seo_description_en' => 'Practical PETG settings: nozzle, bed, fan, pre-print checklist, and FAQ.',
                'seo_keywords' => 'PETG, 3D друк, слайсер, PEI, ретракт, 3Dify',
                'status' => 'published',
                'published_at' => now()->subHour(),
                'is_featured' => true,
                'allow_index' => true,
            ]
        );

        app(BlogPostBlockService::class)->syncBlocks($post, $blocks, app(BlogContentSanitizer::class));

        $this->command?->info('Demo PETG article ready: '.$post->url.' (blocks: '.$post->blocks()->count().')');
    }
}
