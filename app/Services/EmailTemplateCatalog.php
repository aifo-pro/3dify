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
