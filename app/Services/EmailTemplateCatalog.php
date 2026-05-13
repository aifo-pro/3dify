<?php

namespace App\Services;

class EmailTemplateCatalog
{
    /**
     * @return array<string, array{label:string, description:string, variables:list<string>, defaults:array<string, array{subject:string, body:string}>}>
     */
    public static function templates(): array
    {
        $site = ['{{ site.name }}', '{{ site.url }}', '{{ site_name }}', '{{ site_url }}'];
        $user = ['{{ user.name }}', '{{ user.email }}', '{{ user.username }}', '{{ user.display_name }}', '{{ user.locale }}', '{{ user_name }}', '{{ user_email }}'];
        $order = ['{{ order.number }}', '{{ order.total }}', '{{ order.currency }}', '{{ order.url }}', '{{ order_number }}', '{{ order_total }}', '{{ order_currency }}', '{{ order_url }}'];
        $product = ['{{ product.title }}', '{{ product.url }}', '{{ product.slug }}', '{{ product.price }}', '{{ product.currency }}', '{{ product.status }}', '{{ product_title }}', '{{ product_url }}'];
        $balance = ['{{ balance.amount }}', '{{ balance.currency }}', '{{ balance.available }}', '{{ balance.reason }}'];
        $refund = ['{{ refund.reason }}', '{{ refund.message }}', '{{ refund.status }}', '{{ refund.admin_notes }}', '{{ refund.url }}'];
        $tip = ['{{ tip.amount }}', '{{ tip.currency }}', '{{ tip.message }}', '{{ tip.url }}'];
        $post = ['{{ post.title }}', '{{ post.excerpt }}', '{{ post.url }}', '{{ post.cover }}'];
        $payout = ['{{ payout.amount }}', '{{ payout.currency }}', '{{ payout.status }}', '{{ payout.method }}', '{{ payout.url }}'];
        $contact = ['{{ contact.subject }}', '{{ contact.message }}', '{{ contact.sender_name }}'];
        $moderation = ['{{ moderation.note }}', '{{ moderation.reason }}'];
        $authLink = ['{{ link }}'];

        return [
            'registration' => [
                'label' => 'Реєстрація',
                'description' => 'Привітальний лист після створення акаунта.',
                'variables' => self::tokens($site, $user),
                'defaults' => [
                    'uk' => [
                        'subject' => 'Ласкаво просимо до {{ site.name }}',
                        'body' => '<h1>Вітаємо, {{ user.name }}!</h1><p>Ваш акаунт на <b>{{ site.name }}</b> створено. Переходьте в каталог і знаходьте моделі для друку.</p><p><a href="{{ site.url }}">Відкрити 3Dify</a></p>',
                    ],
                    'en' => [
                        'subject' => 'Welcome to {{ site.name }}',
                        'body' => '<h1>Welcome, {{ user.name }}!</h1><p>Your <b>{{ site.name }}</b> account is ready. Browse the marketplace and find models to print.</p><p><a href="{{ site.url }}">Open 3Dify</a></p>',
                    ],
                ],
            ],
            'email_verification' => [
                'label' => 'Підтвердження email',
                'description' => 'Посилання для підтвердження пошти.',
                'variables' => self::tokens($site, $user, $authLink, ['{{ verification.url }}', '{{ verification.expires_minutes }}']),
                'defaults' => [
                    'uk' => [
                        'subject' => 'Підтвердіть email у {{ site.name }}',
                        'body' => '<h1>Підтвердження email</h1><p>Привіт, <b>{{ user.name }}</b>. Натисніть кнопку нижче, щоб підтвердити пошту.</p><p><a href="{{ link }}">Підтвердити email</a></p><p>Посилання дійсне {{ verification.expires_minutes }} хв.</p>',
                    ],
                    'en' => [
                        'subject' => 'Verify your {{ site.name }} email',
                        'body' => '<h1>Email verification</h1><p>Hello, <b>{{ user.name }}</b>. Use the button below to verify your email.</p><p><a href="{{ link }}">Verify email</a></p><p>This link expires in {{ verification.expires_minutes }} minutes.</p>',
                    ],
                ],
            ],
            'password_reset' => [
                'label' => 'Скидання пароля',
                'description' => 'Посилання для зміни пароля.',
                'variables' => self::tokens($site, $user, $authLink, ['{{ reset.url }}', '{{ reset.expires_minutes }}']),
                'defaults' => [
                    'uk' => [
                        'subject' => 'Скидання пароля',
                        'body' => '<h1>Скидання пароля</h1><p>Привіт, <b>{{ user.name }}</b>. Ми отримали запит на скидання пароля для акаунта на <b>{{ site.name }}</b>.</p><p><a href="{{ link }}">Скинути пароль</a></p><p>Якщо ви не надсилали цей запит, просто проігноруйте лист.</p>',
                    ],
                    'en' => [
                        'subject' => 'Reset your password',
                        'body' => '<h1>Password reset</h1><p>Hello, <b>{{ user.name }}</b>. We received a password reset request for your <b>{{ site.name }}</b> account.</p><p><a href="{{ link }}">Reset password</a></p><p>If you did not request this, you can ignore this email.</p>',
                    ],
                ],
            ],
            'purchase_success' => [
                'label' => 'Успішна покупка',
                'description' => 'Покупцю після успішної оплати моделі.',
                'variables' => self::tokens($site, $user, $order, ['{{ download.url }}', '{{ downloads.url }}']),
                'defaults' => [
                    'uk' => [
                        'subject' => 'Замовлення {{ order.number }} оплачено',
                        'body' => '<h1>Дякуємо за покупку!</h1><p>Замовлення <b>{{ order.number }}</b> оплачено на суму <b>{{ order.total }} {{ order.currency }}</b>.</p><p><a href="{{ order.url }}">Відкрити сторінку покупки</a></p>',
                    ],
                    'en' => [
                        'subject' => 'Order {{ order.number }} is paid',
                        'body' => '<h1>Thanks for your purchase!</h1><p>Order <b>{{ order.number }}</b> has been paid: <b>{{ order.total }} {{ order.currency }}</b>.</p><p><a href="{{ order.url }}">Open purchase page</a></p>',
                    ],
                ],
            ],
            'model_sold' => [
                'label' => 'Модель продано',
                'description' => 'Автору після продажу моделі.',
                'variables' => self::tokens($site, $user, $order, $product, ['{{ seller.name }}', '{{ buyer.name }}']),
                'defaults' => [
                    'uk' => [
                        'subject' => 'Новий продаж: {{ product.title }}',
                        'body' => '<h1>Вашу модель купили</h1><p>Модель <b>{{ product.title }}</b> придбано у замовленні <b>{{ order.number }}</b>.</p><p>Сума: <b>{{ order.total }} {{ order.currency }}</b>.</p><p><a href="{{ product.url }}">Відкрити модель</a></p>',
                    ],
                    'en' => [
                        'subject' => 'New sale: {{ product.title }}',
                        'body' => '<h1>Your model was sold</h1><p><b>{{ product.title }}</b> was purchased in order <b>{{ order.number }}</b>.</p><p>Total: <b>{{ order.total }} {{ order.currency }}</b>.</p><p><a href="{{ product.url }}">Open model</a></p>',
                    ],
                ],
            ],
            'model_approved' => [
                'label' => 'Модель схвалено',
                'description' => 'Автору після модерації публікації.',
                'variables' => self::tokens($site, $user, $product),
                'defaults' => [
                    'uk' => ['subject' => 'Модель {{ product.title }} опубліковано', 'body' => '<h1>Модель схвалено</h1><p><b>{{ product.title }}</b> вже доступна покупцям.</p><p><a href="{{ product.url }}">Переглянути сторінку</a></p>'],
                    'en' => ['subject' => '{{ product.title }} is published', 'body' => '<h1>Model approved</h1><p><b>{{ product.title }}</b> is now available to buyers.</p><p><a href="{{ product.url }}">View page</a></p>'],
                ],
            ],
            'model_rejected' => [
                'label' => 'Модель відхилено',
                'description' => 'Автору, якщо модель потребує доопрацювання.',
                'variables' => self::tokens($site, $user, $product, $moderation),
                'defaults' => [
                    'uk' => ['subject' => 'Модель {{ product.title }} потребує доопрацювання', 'body' => '<h1>Потрібні правки</h1><p>Модель <b>{{ product.title }}</b> поки не опубліковано.</p><p><b>Причина:</b> {{ moderation.reason }}</p><p>{{ moderation.note }}</p>'],
                    'en' => ['subject' => '{{ product.title }} needs changes', 'body' => '<h1>Changes required</h1><p><b>{{ product.title }}</b> is not published yet.</p><p><b>Reason:</b> {{ moderation.reason }}</p><p>{{ moderation.note }}</p>'],
                ],
            ],
            'refund_requested' => [
                'label' => 'Заявка на повернення',
                'description' => 'Підтвердження покупцю після створення заявки.',
                'variables' => self::tokens($site, $user, $order, $product, $refund),
                'defaults' => [
                    'uk' => ['subject' => 'Заявку на повернення отримано', 'body' => '<h1>Заявка прийнята</h1><p>Ми отримали заявку щодо замовлення <b>{{ order.number }}</b>.</p><p><b>Причина:</b> {{ refund.reason }}</p><p><a href="{{ refund.url }}">Переглянути заявку</a></p>'],
                    'en' => ['subject' => 'Refund request received', 'body' => '<h1>Request received</h1><p>We received your request for order <b>{{ order.number }}</b>.</p><p><b>Reason:</b> {{ refund.reason }}</p><p><a href="{{ refund.url }}">View request</a></p>'],
                ],
            ],
            'refund_approved' => [
                'label' => 'Повернення підтверджено',
                'description' => 'Покупцю після зарахування коштів на баланс.',
                'variables' => self::tokens($site, $user, $order, $product, $refund, $balance),
                'defaults' => [
                    'uk' => ['subject' => 'Кошти повернено на баланс', 'body' => '<h1>Повернення виконано</h1><p>За замовленням <b>{{ order.number }}</b> на баланс зараховано <b>{{ balance.amount }} {{ balance.currency }}</b>.</p><p>Доступ до файлів цього замовлення закрито.</p>'],
                    'en' => ['subject' => 'Refund credited to your balance', 'body' => '<h1>Refund completed</h1><p><b>{{ balance.amount }} {{ balance.currency }}</b> was credited for order <b>{{ order.number }}</b>.</p><p>File access for this order is now closed.</p>'],
                ],
            ],
            'refund_rejected' => [
                'label' => 'Повернення відхилено',
                'description' => 'Покупцю після відмови у поверненні.',
                'variables' => self::tokens($site, $user, $order, $product, $refund),
                'defaults' => [
                    'uk' => ['subject' => 'Заявку на повернення відхилено', 'body' => '<h1>Заявку відхилено</h1><p>Заявку щодо замовлення <b>{{ order.number }}</b> відхилено.</p><p>{{ refund.admin_notes }}</p>'],
                    'en' => ['subject' => 'Refund request rejected', 'body' => '<h1>Request rejected</h1><p>Your request for order <b>{{ order.number }}</b> was rejected.</p><p>{{ refund.admin_notes }}</p>'],
                ],
            ],
            'balance_credited' => [
                'label' => 'Баланс поповнено',
                'description' => 'Повідомлення про зарахування коштів на баланс.',
                'variables' => self::tokens($site, $user, $balance, $order),
                'defaults' => [
                    'uk' => ['subject' => 'Баланс 3Dify поповнено', 'body' => '<h1>Кошти на балансі</h1><p>На ваш баланс зараховано <b>{{ balance.amount }} {{ balance.currency }}</b>.</p><p>Доступно: <b>{{ balance.available }} {{ balance.currency }}</b>.</p>'],
                    'en' => ['subject' => '3Dify balance credited', 'body' => '<h1>Balance updated</h1><p><b>{{ balance.amount }} {{ balance.currency }}</b> was credited to your balance.</p><p>Available: <b>{{ balance.available }} {{ balance.currency }}</b>.</p>'],
                ],
            ],
            'tip_paid' => [
                'label' => 'Подяка автору оплачена',
                'description' => 'Автору після донату за модель.',
                'variables' => self::tokens($site, $user, $product, $tip, $balance),
                'defaults' => [
                    'uk' => ['subject' => 'Вам надіслали подяку', 'body' => '<h1>Нова подяка</h1><p>Користувач <b>{{ user.name }}</b> підтримав модель <b>{{ product.title }}</b> на <b>{{ tip.amount }} {{ tip.currency }}</b>.</p><p>{{ tip.message }}</p>'],
                    'en' => ['subject' => 'You received a tip', 'body' => '<h1>New tip</h1><p><b>{{ user.name }}</b> supported <b>{{ product.title }}</b> with <b>{{ tip.amount }} {{ tip.currency }}</b>.</p><p>{{ tip.message }}</p>'],
                ],
            ],
            'author_contact' => [
                'label' => 'Контакт автору',
                'description' => 'Приватне повідомлення автору через профіль.',
                'variables' => self::tokens($site, $user, $contact),
                'defaults' => [
                    'uk' => ['subject' => 'Нове повідомлення від {{ contact.sender_name }}', 'body' => '<h1>{{ contact.subject }}</h1><p>{{ contact.message }}</p><p>Відправник: {{ contact.sender_name }} ({{ user.email }})</p>'],
                    'en' => ['subject' => 'New message from {{ contact.sender_name }}', 'body' => '<h1>{{ contact.subject }}</h1><p>{{ contact.message }}</p><p>Sender: {{ contact.sender_name }} ({{ user.email }})</p>'],
                ],
            ],
            'blog_post_published' => [
                'label' => 'Нова стаття блогу',
                'description' => 'Підписникам блогу після публікації нової статті.',
                'variables' => self::tokens($site, $user, $post, $authLink),
                'defaults' => [
                    'uk' => [
                        'subject' => 'Нова стаття на {{ site.name }}: {{ post.title }}',
                        'body' => '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#030712;padding:32px 16px;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;"><tr><td align="center"><table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;border-radius:24px;border:1px solid rgba(16,185,129,.25);background:linear-gradient(180deg,rgba(16,185,129,.08),rgba(9,9,11,.96));overflow:hidden;"><tr><td style="padding:28px 28px 8px;"><p style="margin:0;font-size:11px;font-weight:800;letter-spacing:.18em;text-transform:uppercase;color:#6ee7b7;">3Dify Blog</p><h1 style="margin:12px 0 0;font-size:24px;line-height:1.2;color:#fafafa;">{{ post.title }}</h1><p style="margin:14px 0 0;font-size:15px;line-height:1.6;color:#a1a1aa;">{{ post.excerpt }}</p></td></tr><tr><td style="padding:0 28px 12px;"><img src="{{ post.cover }}" alt="{{ post.title }}" width="544" style="width:100%;max-width:544px;height:auto;display:block;border-radius:16px;border:1px solid rgba(255,255,255,.10);"></td></tr><tr><td style="padding:8px 28px 28px;"><table role="presentation" cellpadding="0" cellspacing="0"><tr><td style="border-radius:16px;background:#34d399;"><a href="{{ post.url }}" style="display:inline-block;padding:14px 22px;font-size:14px;font-weight:800;color:#0a0a0a;text-decoration:none;">Читати статтю</a></td></tr></table><p style="margin:22px 0 0;font-size:12px;line-height:1.5;color:#71717a;">Відписатися від розсилки: <a href="{{ link }}" style="color:#6ee7b7;">посилання</a></p></td></tr></table></td></tr></table>',
                    ],
                    'en' => [
                        'subject' => 'New article on {{ site.name }}: {{ post.title }}',
                        'body' => '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#030712;padding:32px 16px;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;"><tr><td align="center"><table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;border-radius:24px;border:1px solid rgba(16,185,129,.25);background:linear-gradient(180deg,rgba(16,185,129,.08),rgba(9,9,11,.96));overflow:hidden;"><tr><td style="padding:28px 28px 8px;"><p style="margin:0;font-size:11px;font-weight:800;letter-spacing:.18em;text-transform:uppercase;color:#6ee7b7;">3Dify Blog</p><h1 style="margin:12px 0 0;font-size:24px;line-height:1.2;color:#fafafa;">{{ post.title }}</h1><p style="margin:14px 0 0;font-size:15px;line-height:1.6;color:#a1a1aa;">{{ post.excerpt }}</p></td></tr><tr><td style="padding:0 28px 12px;"><img src="{{ post.cover }}" alt="{{ post.title }}" width="544" style="width:100%;max-width:544px;height:auto;display:block;border-radius:16px;border:1px solid rgba(255,255,255,.10);"></td></tr><tr><td style="padding:8px 28px 28px;"><table role="presentation" cellpadding="0" cellspacing="0"><tr><td style="border-radius:16px;background:#34d399;"><a href="{{ post.url }}" style="display:inline-block;padding:14px 22px;font-size:14px;font-weight:800;color:#0a0a0a;text-decoration:none;">Read article</a></td></tr></table><p style="margin:22px 0 0;font-size:12px;line-height:1.5;color:#71717a;">Unsubscribe: <a href="{{ link }}" style="color:#6ee7b7;">link</a></p></td></tr></table></td></tr></table>',
                    ],
                ],
            ],
            'payout_requested' => [
                'label' => 'Заявка на виплату',
                'description' => 'Автору після створення заявки на виплату.',
                'variables' => self::tokens($site, $user, $payout),
                'defaults' => [
                    'uk' => ['subject' => 'Заявку на виплату створено', 'body' => '<h1>Заявку прийнято</h1><p>Сума: <b>{{ payout.amount }} {{ payout.currency }}</b>.</p><p>Метод: {{ payout.method }}. Статус: {{ payout.status }}.</p>'],
                    'en' => ['subject' => 'Payout request created', 'body' => '<h1>Request received</h1><p>Amount: <b>{{ payout.amount }} {{ payout.currency }}</b>.</p><p>Method: {{ payout.method }}. Status: {{ payout.status }}.</p>'],
                ],
            ],
        ];
    }

    public static function labels(): array
    {
        return collect(self::templates())->mapWithKeys(fn ($data, $key) => [$key => __($data['label'])])->all();
    }

    /**
     * @return array<string, list<string>>
     */
    public static function placeholderMap(): array
    {
        return collect(self::templates())->mapWithKeys(fn ($data, $key) => [$key => $data['variables']])->all();
    }

    private static function tokens(array ...$groups): array
    {
        return array_values(array_unique(array_merge(...$groups)));
    }
}
