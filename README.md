# 3Dify MVP

3Dify - Laravel MVP маркетплейса 3D-моделей для 3D-друку. Проект містить публічний каталог, сторінку моделі з Three.js viewer, кабінет автора, захищене завантаження файлів після покупки, aifo.pro payment scaffold, email-події, SEO/переклади/налаштування через адмін-зону.

## Стек

- Laravel 13, Blade, Tailwind CSS, Alpine.js
- MySQL або SQLite для локальної розробки
- Eloquent ORM, migrations, seeders, policies, middleware
- Laravel Mail + Queue
- GitHub login через `laravel/socialite` після встановлення пакета
- Telegram Login endpoint
- Three.js viewer
- Filament-ready admin data model; якщо Composer доступний, можна додати `filament/filament` і під'єднати ресурси до вже створених моделей

## Встановлення

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Мінімальні `.env` значення:

```env
APP_NAME=3Dify
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=3dify
DB_USERNAME=root
DB_PASSWORD=
QUEUE_CONNECTION=database
MAIL_MAILER=smtp
```

## Міграції та seed

```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
```

Seed створює:

- `admin@3dify.local` / `password`
- `maker@3dify.local` / `password`
- базові категорії, теги, ліцензію, SEO, email templates і demo-модель

## Запуск

```bash
php artisan serve
npm run dev
php artisan queue:work
```

Production assets:

```bash
npm run build
```

## SMTP

Налаштуйте SMTP у `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=user@example.com
MAIL_PASSWORD=secret
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@3dify.local
MAIL_FROM_NAME=3Dify
```

У адмінці `/admin/content` редагуються email templates для покупок і продажів. Breeze вже покриває реєстрацію, підтвердження email і reset password.

## aifo.pro

Платежі винесені в `App\Services\AifoPaymentService`.

1. В адмінці `/admin/content` додайте `payments.aifo_merchant_id`.
2. Для реального checkout додайте `payments.aifo_endpoint` і `payments.aifo_api_key`.
3. Для захищеного webhook додайте `payments.aifo_webhook_secret`. Підпис очікується в `X-Aifo-Signature` як `hash_hmac('sha256', raw_body, secret)`.
4. Route webhook: `POST /payments/aifo/webhook`.
5. Якщо endpoint/API key не задані, checkout показує demo-кнопку підтвердження оплати для локального тесту.
6. Безкоштовні моделі автоматично створюють paid order і відкривають завантаження.

## Адмін-зона

URL: `/admin`

Доступ мають ролі `admin` і `moderator`.

Розділи:

- користувачі та ролі
- моделі та модерація
- категорії
- теги
- замовлення
- платежі
- SEO
- переклади
- email templates
- налаштування сайту, логотипу, favicon і платежів

## Файли та безпека

Підтримуються `STL`, `OBJ`, `GLB`, `GLTF`, `ZIP`, `3MF`.

Платні source-файли зберігаються на private disk (`storage/app/private`) і не віддаються напряму. Route завантаження перевіряє:

- користувач авторизований
- модель безкоштовна або куплена
- користувач є автором, moderator або admin

Preview-файли для viewer зберігаються на public disk.

## GitHub і Telegram login

GitHub routes вже додані:

- `/auth/github/redirect`
- `/auth/github/callback`

Потрібно встановити пакет, якщо Composer доступний:

```bash
composer require laravel/socialite
```

Telegram endpoint: `POST /auth/telegram`. Для production додайте перевірку hash за bot token у `SocialAuthController`.
Telegram Login Widget також підтримує `GET /auth/telegram`. Hash вже перевіряється через `TELEGRAM_BOT_TOKEN` або setting `auth.telegram_bot_token`.

```env
TELEGRAM_BOT_USERNAME=your_bot
TELEGRAM_BOT_TOKEN=123456:secret
```

## Корисні settings keys

- `site.name`
- `brand.logo_path`
- `brand.favicon_path`
- `auth.telegram_bot_token`
- `payments.aifo_merchant_id`
- `payments.aifo_endpoint`
- `payments.aifo_api_key`
- `payments.aifo_webhook_secret`

## Перевірка

```bash
php artisan route:list
php artisan migrate --pretend
php artisan test
npm run build
```

Поточний стан перевірено: PHP syntax clean, routes loaded, tests passed, Vite build passed.
