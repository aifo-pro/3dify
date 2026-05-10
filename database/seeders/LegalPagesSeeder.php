<?php

namespace Database\Seeders;

use App\Models\LegalPage;
use Illuminate\Database\Seeder;

/**
 * Seeds the canonical set of footer / legal pages with rich starter content.
 *
 * Idempotent: existing records are NOT overwritten so admin edits are preserved.
 * Re-running the seeder only inserts pages that do not yet exist.
 */
class LegalPagesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $page) {
            LegalPage::firstOrCreate(
                ['slug' => $page['slug'], 'locale' => $page['locale']],
                [
                    'title' => $page['title'],
                    'subtitle' => $page['subtitle'] ?? null,
                    'body' => $page['body'],
                    'meta_title' => $page['meta_title'] ?? $page['title'],
                    'meta_description' => $page['meta_description'] ?? null,
                    'is_published' => true,
                    'sort_order' => $page['sort_order'] ?? 0,
                ]
            );
        }
    }

    /**
     * @return array<int, array<string, string|int|null>>
     */
    private function pages(): array
    {
        return array_merge(
            $this->ukrainian(),
            $this->english(),
        );
    }

    /** ---------------------------------------------------------------------
     * Ukrainian content
     * --------------------------------------------------------------------*/
    private function ukrainian(): array
    {
        return [
            [
                'slug' => 'publishing-rules',
                'locale' => 'uk',
                'title' => 'Правила публікації моделей',
                'subtitle' => 'Як підготувати файл, опис і ціну, щоб ваш товар швидко пройшов модерацію та сподобався покупцям.',
                'meta_description' => 'Правила публікації 3D-моделей у маркетплейсі 3Dify: технічні вимоги, опис, ціни, ліцензії та модерація.',
                'sort_order' => 10,
                'body' => <<<'HTML'
<h2>1. Хто може публікувати моделі</h2>
<p>Завантажувати моделі може будь-який зареєстрований користувач, який підтвердив email і має статус автора. Ви несете повну відповідальність за те, що володієте правами на 3D-модель або маєте дозвіл правовласника на її продаж/розповсюдження.</p>

<h2>2. Технічні вимоги до файлів</h2>
<ul>
    <li>Підтримувані формати: <strong>STL, OBJ, 3MF, GLB, GLTF, ZIP</strong>.</li>
    <li>Максимальний розмір одного файлу — <strong>200 MB</strong>, архіву — <strong>500 MB</strong>.</li>
    <li>Файли повинні бути «manifold» (без діркувих стінок), масштабовані в міліметрах і перевірені у слайсері.</li>
    <li>Якщо модель розрахована на FDM/SLA-друк — вкажіть рекомендовані висоту шару, заповнення та орієнтацію.</li>
    <li>Архіви ZIP мають містити лише файли моделі, інструкції та превʼю — без виконуваних файлів.</li>
</ul>

<h2>3. Зображення й превʼю</h2>
<ul>
    <li>Мінімум одне головне зображення у форматі JPG/PNG/WEBP, 1600×1200 пікселів або більше.</li>
    <li>Рендери або реальні фото — без водяних знаків чужих сервісів.</li>
    <li>Опціонально: GIF або відео для демонстрації обертання.</li>
</ul>

<h2>4. Назва, опис, теги</h2>
<ul>
    <li>Назва — українською та англійською, без CAPS LOCK і кликбейту.</li>
    <li>Опис має містити: розміри, кількість деталей, рекомендації з друку, що входить у комплект.</li>
    <li>Категорія + 3–8 тегів. Не дублюйте теги синонімами.</li>
</ul>

<h2>5. Ціна та ліцензія</h2>
<p>Автор вільно встановлює ціну в гривнях (UAH). Маркетплейс утримує комісію згідно з тарифним планом (за замовчуванням 15%). Ви можете обрати одну з ліцензій:</p>
<ul>
    <li><strong>Personal</strong> — для особистого друку без права на перепродаж файлу.</li>
    <li><strong>Commercial</strong> — дозволяє продавати надруковані вироби.</li>
    <li><strong>Free</strong> — безкоштовне завантаження зі збереженням авторства.</li>
</ul>

<h2>6. Заборонений контент</h2>
<ul>
    <li>Зброя, що порушує локальне законодавство (вогнепальна, її компоненти).</li>
    <li>Контент, що порушує авторські та торговельні права (Disney, Games Workshop, Marvel тощо без офіційної ліцензії).</li>
    <li>Контент 18+, відверто сексуального характеру, дискримінаційний або такий, що пропагує насильство.</li>
    <li>Файли з шкідливим кодом, прихованими виконуваними файлами.</li>
</ul>

<h2>7. Модерація</h2>
<p>Кожна нова публікація проходить ручну перевірку командою 3Dify протягом <strong>24–48 годин</strong>. Ми перевіряємо технічну якість, опис, відповідність ліцензії та правилам. У разі відмови ви отримаєте лист з поясненням та рекомендаціями.</p>

<h2>8. Оновлення та зняття з продажу</h2>
<ul>
    <li>Ви можете оновити файли або опис у будь-який момент — нова версія знову проходить швидку перевірку.</li>
    <li>Зняття з продажу можливе у кабінеті автора. Уже куплені файли залишаються доступними у покупців.</li>
</ul>

<h2>9. Що може отримати порушник</h2>
<p>Перше попередження → тимчасове приховування моделі → блокування облікового запису та виплат. У разі шахрайства ми передаємо інформацію правоохоронним органам та правовласникам.</p>

<h2>10. Маєте запитання?</h2>
<p>Звʼяжіться з командою через сторінку <a href="/page/contact">контактів</a> або напишіть на <a href="mailto:authors@3dify.dev">authors@3dify.dev</a>.</p>
HTML,
            ],
            [
                'slug' => 'copyright',
                'locale' => 'uk',
                'title' => 'Авторські права та DMCA',
                'subtitle' => 'Як ми працюємо з правами власності та які кроки робити, якщо ви знайшли порушення.',
                'meta_description' => 'Політика авторських прав 3Dify: правила завантаження, повідомлення про порушення, контрповідомлення та контакти юридичної команди.',
                'sort_order' => 20,
                'body' => <<<'HTML'
<h2>1. Базовий принцип</h2>
<p>3Dify поважає інтелектуальну власність. Усі моделі, текстури, рендери, описи й фото на платформі залишаються власністю їхніх авторів або правовласників. Завантажуючи контент на маркетплейс, автор підтверджує, що володіє відповідними правами або має дозвіл на його розповсюдження.</p>

<h2>2. Що дозволено покупцям</h2>
<p>Купуючи модель, ви отримуєте ліцензію відповідно до вибраного автором тарифу (Personal, Commercial або Free). Якщо інше прямо не вказано:</p>
<ul>
    <li>Особиста ліцензія дозволяє друк для себе, друзів і близьких — без подальшого продажу самого файла.</li>
    <li>Комерційна ліцензія додає право продавати надруковані вироби, але не сам файл.</li>
    <li>Безкоштовна ліцензія зберігає авторство — обовʼязкове згадування автора у разі публічного показу.</li>
</ul>

<h2>3. Що заборонено</h2>
<ul>
    <li>Перепродаж або поширення файлів моделей у будь-якому форматі.</li>
    <li>Завантаження моделей на торрент-трекери, в Telegram-канали, у магазини на кшталт CGTrader/Cults без згоди автора.</li>
    <li>Видалення підпису автора, ID-маркувань 3Dify та watermark із зображень.</li>
    <li>Створення «ремоделей», які лише копіюють чужий дизайн без істотних змін.</li>
</ul>

<h2>4. Повідомлення про порушення (DMCA-style)</h2>
<p>Якщо ви виявили модель, яка порушує ваші права, надішліть запит на адресу <a href="mailto:legal@3dify.dev">legal@3dify.dev</a>. Лист має містити:</p>
<ol>
    <li>Ваше імʼя, контактні дані, опис обʼєкта прав.</li>
    <li>Посилання на оригінал (ваш сайт, портфоліо, реєстраційне свідоцтво).</li>
    <li>Посилання на сторінку 3Dify з порушенням.</li>
    <li>Підтвердження сумлінності й електронний підпис.</li>
</ol>
<p>Команда розглядає звернення впродовж <strong>5 робочих днів</strong>. Підтверджені порушення видаляються негайно, а порушникам надсилається попередження або накладається блокування.</p>

<h2>5. Контрповідомлення</h2>
<p>Якщо ви впевнені, що видалення помилкове, ви маєте право подати контрповідомлення на ту ж адресу. У запиті повідомте підстави, надайте докази власності, контактні дані та зробіть заяву під присягою. Ми відновимо контент після узгодження зі сторонами.</p>

<h2>6. Робота з брендами та офіційні партнери</h2>
<p>Якщо ваша компанія володіє великим портфелем IP і хоче офіційно співпрацювати з 3Dify (white-list автори, ексклюзивні релізи, ліцензійні збори) — напишіть на <a href="mailto:partners@3dify.dev">partners@3dify.dev</a>.</p>
HTML,
            ],
            [
                'slug' => 'terms',
                'locale' => 'uk',
                'title' => 'Умови використання',
                'subtitle' => 'Юридичні умови, які регулюють користування маркетплейсом 3Dify.',
                'meta_description' => 'Умови використання сайту 3Dify: реєстрація, замовлення, повернення, обов’язки сторін, обмеження відповідальності.',
                'sort_order' => 30,
                'body' => <<<'HTML'
<h2>1. Загальні положення</h2>
<p>Ці Умови використання («<strong>Умови</strong>») регулюють відносини між адміністратором сайту 3Dify («<strong>Платформа</strong>», «<strong>ми</strong>») та користувачами («<strong>ви</strong>»). Користуючись сайтом, ви підтверджуєте, що ознайомились та погоджуєтесь з усіма пунктами цих Умов і нашою <a href="/page/privacy">Політикою приватності</a>.</p>

<h2>2. Реєстрація та обліковий запис</h2>
<ul>
    <li>Зареєструватися можуть особи від 16 років. Якщо ви молодші — потрібен дозвіл батьків чи опікуна.</li>
    <li>Ви відповідаєте за збереження своїх облікових даних та активність вашого облікового запису.</li>
    <li>Заборонено реєструвати кілька облікових записів з метою обходу блокувань або шахрайства.</li>
</ul>

<h2>3. Замовлення та оплата</h2>
<ul>
    <li>Ціни вказуються в гривнях (UAH). Платежі обробляються через інтеграції Stripe / AIFO / Wayforpay.</li>
    <li>Після успішної оплати ви отримуєте захищене посилання на завантаження файлу та лист з кваитанцією.</li>
    <li>Платформа утримує комісію автора згідно з обраним тарифом (стандарт — 15%).</li>
</ul>

<h2>4. Цифрові товари та повернення</h2>
<p>Усі товари в маркетплейсі — цифрові файли, які доступні одразу після оплати. Відповідно до законодавства про електронну комерцію, ви погоджуєтесь, що право на відмову від договору на цифрові товари припиняється з моменту початку завантаження.</p>
<p>Винятки (повернення можливе) — якщо файл фізично пошкоджений або не відповідає опису. У такому разі звертайтеся на <a href="mailto:support@3dify.dev">support@3dify.dev</a> протягом 14 днів.</p>

<h2>5. Поведінка користувачів</h2>
<ul>
    <li>Заборонено завантажувати, поширювати або обмінюватися контентом, що порушує права третіх осіб.</li>
    <li>Заборонено намагатися зламати, ддосити або скрапити Платформу.</li>
    <li>Заборонено використовувати ботів для збору даних, штучного підвищення рейтингу або накрутки відгуків.</li>
</ul>

<h2>6. Інтелектуальна власність</h2>
<p>Контент, створений авторами, належить авторам. Бренд 3Dify, дизайн інтерфейсу, тексти на сторінках, логотип і фірмові кольори належать Платформі.</p>

<h2>7. Обмеження відповідальності</h2>
<p>Ми надаємо Платформу «як є». Ми не гарантуємо безперервної роботи й не відповідаємо за збитки, які виникли через недоліки 3D-друку, неправильний вибір параметрів, несумісність з обладнанням користувача чи дії третіх осіб.</p>

<h2>8. Зміна Умов</h2>
<p>Ми можемо оновлювати ці Умови. Користувачі отримують повідомлення електронною поштою або у банері на сайті. Продовження використання Платформи після публікації нової редакції означає вашу згоду.</p>

<h2>9. Юрисдикція та контакти</h2>
<p>Ці Умови регулюються правом України. Спірні питання вирішуються у судах за місцезнаходженням Платформи. Контакт для юридичних питань — <a href="mailto:legal@3dify.dev">legal@3dify.dev</a>.</p>
HTML,
            ],
            [
                'slug' => 'contact',
                'locale' => 'uk',
                'title' => 'Контакти',
                'subtitle' => 'Найшвидший спосіб звʼязатись з командою 3Dify.',
                'meta_description' => 'Email, адреса, соціальні мережі та форма звязку з командою маркетплейсу 3Dify.',
                'sort_order' => 40,
                'body' => <<<'HTML'
<h2>Електронна пошта</h2>
<ul>
    <li><strong>Загальні питання:</strong> <a href="mailto:info@3dify.dev">info@3dify.dev</a></li>
    <li><strong>Підтримка покупців:</strong> <a href="mailto:support@3dify.dev">support@3dify.dev</a></li>
    <li><strong>Автори і виплати:</strong> <a href="mailto:authors@3dify.dev">authors@3dify.dev</a></li>
    <li><strong>Юридичні запити, DMCA:</strong> <a href="mailto:legal@3dify.dev">legal@3dify.dev</a></li>
    <li><strong>Партнерство і медіа:</strong> <a href="mailto:partners@3dify.dev">partners@3dify.dev</a></li>
</ul>

<h2>Робочі години</h2>
<p>Понеділок — пʼятниця, 9:00–18:00 (Europe/Kyiv). Звернення поза цим часом обробляються в перший робочий день.</p>

<h2>Як отримати швидку відповідь</h2>
<ol>
    <li>Опишіть проблему чітко: що відбувається, що мало б відбутись.</li>
    <li>Прикладіть номер замовлення, скріншоти, посилання на сторінку.</li>
    <li>Укажіть браузер / ОС, якщо мова про технічну проблему.</li>
</ol>

<h2>Реквізити</h2>
<p>3Dify Marketplace<br>
Україна, м. Київ<br>
EDRPOU / податковий номер вкажіть тут після оформлення.</p>

<h2>Соціальні мережі</h2>
<p>Слідкуйте за релізами та новинами:</p>
<ul>
    <li>Telegram-канал — анонси нових моделей</li>
    <li>X / Twitter — короткі апдейти</li>
    <li>YouTube — гайди та огляди</li>
</ul>
HTML,
            ],
            [
                'slug' => 'faq',
                'locale' => 'uk',
                'title' => 'Поширені питання',
                'subtitle' => 'Найчастіші запитання покупців та авторів — з лаконічними відповідями.',
                'meta_description' => 'FAQ маркетплейсу 3Dify: як купити, як завантажити модель, як друкувати STL/OBJ/3MF, виплати авторам.',
                'sort_order' => 50,
                'body' => <<<'HTML'
<h2>Покупцям</h2>

<details open>
    <summary><strong>У яких форматах я отримаю модель?</strong></summary>
    <p>Залежно від моделі, це STL, OBJ, 3MF, GLB або ZIP-архів з усіма файлами та інструкцією. Всі формати працюють у популярних слайсерах (Cura, PrusaSlicer, Bambu Studio, Lychee).</p>
</details>

<details>
    <summary><strong>Як завантажити куплений файл?</strong></summary>
    <p>Перейдіть у <em>Кабінет → Мої покупки</em>. Поряд з кожним замовленням буде кнопка <strong>Завантажити</strong>. Посилання захищене — діє 24 години та повторно генерується автоматично.</p>
</details>

<details>
    <summary><strong>Я можу повернути гроші, якщо файл не сподобався?</strong></summary>
    <p>Цифрові товари не повертаються після завантаження. Виняток — файл пошкоджений або не відповідає опису. Напишіть на <a href="mailto:support@3dify.dev">support@3dify.dev</a>.</p>
</details>

<details>
    <summary><strong>Чи можна друкувати модель на продаж?</strong></summary>
    <p>Так, якщо ви придбали комерційну ліцензію. Особиста ліцензія дозволяє лише друк для себе.</p>
</details>

<h2>Авторам</h2>

<details>
    <summary><strong>Як стати автором і завантажити свою модель?</strong></summary>
    <p>Зареєструйтеся, у профілі натисніть «Стати автором», заповніть платіжні дані та натисніть «Завантажити модель». Перша публікація проходить ручну модерацію.</p>
</details>

<details>
    <summary><strong>Скільки коштує комісія 3Dify?</strong></summary>
    <p>Стандартний тариф — 15% з кожного продажу. Для топ-авторів і ексклюзивних релізів передбачені знижені ставки.</p>
</details>

<details>
    <summary><strong>Як отримати виплату?</strong></summary>
    <p>Виплати щомісячні, на банківський рахунок, Wise або криптогаманець. Мінімальна сума — 800 грн. Заявку подаєте у кабінеті автора.</p>
</details>

<details>
    <summary><strong>Що робити, якщо мою модель скопіювали?</strong></summary>
    <p>Надішліть DMCA-запит на <a href="mailto:legal@3dify.dev">legal@3dify.dev</a> з посиланнями на оригінал та порушення. Розгляд — до 5 робочих днів.</p>
</details>

<h2>Технічні питання</h2>

<details>
    <summary><strong>Які формати найкращі для 3D-друку?</strong></summary>
    <p>Для FDM/SLA — STL та 3MF. Якщо у файлі важлива колірна інформація (наприклад, для Bambu з AMS) — оберіть 3MF. Для перегляду на сайті — GLB.</p>
</details>

<details>
    <summary><strong>Сайт працює повільно. Що робити?</strong></summary>
    <p>Перевірте швидкість інтернету, очистіть кеш браузера або відкрийте у режимі інкогніто. Якщо проблема залишається — напишіть нам із вказівкою браузера, ОС, скріншотом DevTools (вкладка Network).</p>
</details>
HTML,
            ],
            [
                'slug' => 'privacy',
                'locale' => 'uk',
                'title' => 'Політика приватності',
                'subtitle' => 'Які дані ми збираємо, навіщо та як їх захищаємо.',
                'meta_description' => 'Політика приватності 3Dify: збір, обробка та захист персональних даних відповідно до GDPR та законодавства України.',
                'sort_order' => 60,
                'body' => <<<'HTML'
<h2>1. Хто є контролером даних</h2>
<p>Контролером ваших персональних даних виступає 3Dify Marketplace. Звертатися з будь-якими питаннями приватності можна на <a href="mailto:privacy@3dify.dev">privacy@3dify.dev</a>.</p>

<h2>2. Які дані ми збираємо</h2>
<ul>
    <li><strong>Облікові дані:</strong> імʼя, email, пароль (зберігається у вигляді хешу), нікнейм.</li>
    <li><strong>Платіжні дані:</strong> опрацьовуються нашими провайдерами (Stripe, AIFO, Wayforpay). Ми не зберігаємо повних номерів карт.</li>
    <li><strong>Технічні дані:</strong> IP, браузер, ОС, refresh-токен, фінгерпринт пристрою — для безпеки і захисту від ботів.</li>
    <li><strong>Контент користувача:</strong> моделі, превʼю, описи, відгуки, повідомлення з підтримкою.</li>
    <li><strong>Cookies:</strong> сесійні (автентифікація) та аналітичні (Google Analytics, з вашою згодою).</li>
</ul>

<h2>3. Юридичні підстави обробки</h2>
<ul>
    <li>Договір — для надання послуг маркетплейсу.</li>
    <li>Згода — для маркетингових розсилок та аналітичних cookies.</li>
    <li>Законні інтереси — для безпеки, виявлення шахрайства, покращення сервісу.</li>
    <li>Виконання правових вимог — для бухгалтерії, податків.</li>
</ul>

<h2>4. Кому ми передаємо дані</h2>
<p>Ваші дані можуть бути передані:</p>
<ul>
    <li>Платіжним провайдерам (для оплати).</li>
    <li>Поштовому провайдеру Amazon SES (для надсилання листів).</li>
    <li>CDN та хостинг-партнерам (для доставки контенту).</li>
    <li>Аналітичним сервісам Google (анонімізовано).</li>
</ul>
<p>Ми не продаємо ваші дані третім особам.</p>

<h2>5. Скільки часу ми зберігаємо дані</h2>
<ul>
    <li>Облікові дані — поки активний обліковий запис + 12 місяців після видалення.</li>
    <li>Платіжні документи — 7 років (вимоги бухгалтерського обліку).</li>
    <li>Технічні логи — до 90 днів.</li>
    <li>Cookies — від сесії до 13 місяців.</li>
</ul>

<h2>6. Ваші права</h2>
<p>Згідно з GDPR та законом «Про захист персональних даних», ви маєте право:</p>
<ul>
    <li>Отримати копію своїх даних.</li>
    <li>Виправити неточності.</li>
    <li>Видалити дані («право бути забутим»), якщо немає правових підстав їх зберігати.</li>
    <li>Обмежити обробку, заперечити проти неї.</li>
    <li>Перенести дані до іншого сервісу.</li>
</ul>
<p>Запити надсилайте на <a href="mailto:privacy@3dify.dev">privacy@3dify.dev</a> з вказанням облікового запису.</p>

<h2>7. Безпека</h2>
<p>Ми використовуємо HTTPS, зберігаємо паролі у вигляді bcrypt-хешів, регулярно оновлюємо залежності та проводимо аудит. Платіжні дані не торкаються наших серверів — їх обробляють сертифіковані PCI DSS провайдери.</p>

<h2>8. Cookies</h2>
<p>При першому відвідуванні сайту з’являється банер згоди на cookies. Ви можете обрати лише необхідні або погодитись на аналітику й маркетинг. Налаштування можна змінити у будь-який момент через посилання у футері.</p>

<h2>9. Зміни до політики</h2>
<p>Ми можемо оновлювати цю політику. Дата останнього оновлення завжди вказана внизу. Про суттєві зміни ми повідомимо листом і на сайті.</p>
HTML,
            ],
        ];
    }

    /** ---------------------------------------------------------------------
     * English content (mirrors UK)
     * --------------------------------------------------------------------*/
    private function english(): array
    {
        return [
            [
                'slug' => 'publishing-rules',
                'locale' => 'en',
                'title' => 'Publishing Rules',
                'subtitle' => 'How to prepare files, descriptions and pricing so your model passes review quickly.',
                'meta_description' => 'Publishing rules for the 3Dify 3D model marketplace: technical requirements, descriptions, pricing, licenses and moderation.',
                'sort_order' => 10,
                'body' => <<<'HTML'
<h2>1. Who can publish models</h2>
<p>Any registered user with a verified email and author status can upload models. By publishing you confirm that you own the rights to the model or have explicit permission from the rights holder to sell or distribute it.</p>

<h2>2. Technical file requirements</h2>
<ul>
    <li>Supported formats: <strong>STL, OBJ, 3MF, GLB, GLTF, ZIP</strong>.</li>
    <li>Max single file size — <strong>200 MB</strong>, archive — <strong>500 MB</strong>.</li>
    <li>Files must be manifold (watertight), millimeter-scaled, and tested in a slicer.</li>
    <li>For FDM/SLA prints — provide recommended layer height, infill and orientation.</li>
    <li>ZIP archives must contain only model files, instructions and previews — no executables.</li>
</ul>

<h2>3. Images and previews</h2>
<ul>
    <li>At least one main image in JPG/PNG/WEBP, 1600×1200 px or larger.</li>
    <li>Renders or real photos — no third-party watermarks.</li>
    <li>Optional GIF or video showing rotation.</li>
</ul>

<h2>4. Title, description, tags</h2>
<ul>
    <li>Title in Ukrainian and English, no CAPS LOCK or clickbait.</li>
    <li>Description must include: dimensions, parts count, print settings, what is included.</li>
    <li>Category + 3–8 tags. Avoid duplicating tags via synonyms.</li>
</ul>

<h2>5. Pricing and licensing</h2>
<p>Authors set their own price in Ukrainian hryvnia (UAH). The marketplace charges a commission per the active plan (default 15%). Choose a license:</p>
<ul>
    <li><strong>Personal</strong> — printing for personal use only, no file resale.</li>
    <li><strong>Commercial</strong> — print and sell physical copies.</li>
    <li><strong>Free</strong> — free download, attribution required.</li>
</ul>

<h2>6. Prohibited content</h2>
<ul>
    <li>Weapons that violate local law (firearms or their components).</li>
    <li>Content that infringes copyright or trademarks (Disney, Games Workshop, Marvel etc.) without an official license.</li>
    <li>18+ content, sexually explicit material, hate or violence promotion.</li>
    <li>Files with malware or hidden executables.</li>
</ul>

<h2>7. Moderation</h2>
<p>Every new submission goes through manual review by the 3Dify team within <strong>24–48 hours</strong>. We check technical quality, description, license fit and rule compliance. If we reject your model, you receive an email with reasoning and recommendations.</p>

<h2>8. Updates and unpublishing</h2>
<ul>
    <li>You can update files or copy at any time — new versions go through fast review.</li>
    <li>You can unpublish from the author dashboard. Already-purchased files remain available to buyers.</li>
</ul>

<h2>9. Penalties</h2>
<p>First warning → temporary unpublishing → account and payout suspension. In case of fraud we cooperate with law enforcement and rights holders.</p>

<h2>10. Questions?</h2>
<p>Reach the team via <a href="/page/contact">contact page</a> or write to <a href="mailto:authors@3dify.dev">authors@3dify.dev</a>.</p>
HTML,
            ],
            [
                'slug' => 'copyright',
                'locale' => 'en',
                'title' => 'Copyright & DMCA',
                'subtitle' => 'How we handle ownership rights and what to do if you find an infringement.',
                'meta_description' => '3Dify copyright policy: upload rules, infringement notice procedure, counter-notices, and legal team contacts.',
                'sort_order' => 20,
                'body' => <<<'HTML'
<h2>1. Core principle</h2>
<p>3Dify respects intellectual property. All models, textures, renders, descriptions and photos remain the property of their authors or rights holders. By uploading, the author confirms ownership or permission to distribute.</p>

<h2>2. What buyers can do</h2>
<p>Purchasing a model grants a license per the author’s chosen tier (Personal, Commercial or Free). Unless stated otherwise:</p>
<ul>
    <li>Personal license — print for yourself, friends and family — no file resale.</li>
    <li>Commercial license — adds the right to sell printed parts, but not the file itself.</li>
    <li>Free license — preserves attribution; mention the author in any public showcase.</li>
</ul>

<h2>3. What is forbidden</h2>
<ul>
    <li>Reselling or sharing model files in any format.</li>
    <li>Uploading models to torrent trackers, Telegram channels, CGTrader/Cults etc. without author consent.</li>
    <li>Removing author credits, 3Dify ID markers or watermarks from images.</li>
    <li>Creating "remodels" that simply copy someone else’s design without significant changes.</li>
</ul>

<h2>4. Infringement notice (DMCA-style)</h2>
<p>If you find a model that infringes your rights, send a request to <a href="mailto:legal@3dify.dev">legal@3dify.dev</a>. Include:</p>
<ol>
    <li>Your name, contact details, description of the work.</li>
    <li>Link to the original (your site, portfolio, registration certificate).</li>
    <li>Link to the infringing 3Dify page.</li>
    <li>Good-faith statement and electronic signature.</li>
</ol>
<p>The team responds within <strong>5 business days</strong>. Confirmed infringements are removed immediately and offenders receive warnings or account suspensions.</p>

<h2>5. Counter-notices</h2>
<p>If you believe the takedown is mistaken, you can submit a counter-notice to the same address. Provide ownership evidence, contact details and a sworn statement. We restore content after the parties reach agreement.</p>

<h2>6. Brands and official partners</h2>
<p>If your company owns a large IP portfolio and wants to officially partner with 3Dify (white-listed authors, exclusive releases, licensing fees) — write to <a href="mailto:partners@3dify.dev">partners@3dify.dev</a>.</p>
HTML,
            ],
            [
                'slug' => 'terms',
                'locale' => 'en',
                'title' => 'Terms of Service',
                'subtitle' => 'Legal terms governing the use of the 3Dify marketplace.',
                'meta_description' => 'Terms of Service for 3Dify: registration, orders, refunds, party obligations, liability limits.',
                'sort_order' => 30,
                'body' => <<<'HTML'
<h2>1. General provisions</h2>
<p>These Terms of Service ("<strong>Terms</strong>") govern relations between the operator of 3Dify ("<strong>Platform</strong>", "<strong>we</strong>") and users ("<strong>you</strong>"). By using the site you confirm acceptance of all clauses and our <a href="/page/privacy">Privacy Policy</a>.</p>

<h2>2. Registration and account</h2>
<ul>
    <li>Users aged 16+ may register. Younger users need parental consent.</li>
    <li>You are responsible for safeguarding your credentials and account activity.</li>
    <li>Multiple accounts to circumvent bans or commit fraud are prohibited.</li>
</ul>

<h2>3. Orders and payment</h2>
<ul>
    <li>Prices are listed in UAH (hryvnia). Payments are processed via Stripe / AIFO / Wayforpay integrations.</li>
    <li>After successful payment you receive a secure download link and an email receipt.</li>
    <li>The Platform retains a commission per the chosen plan (default 15%).</li>
</ul>

<h2>4. Digital goods and refunds</h2>
<p>All marketplace items are digital files delivered immediately. Per e-commerce law you waive your right of withdrawal once the download begins.</p>
<p>Exceptions (refunds possible) — if the file is corrupted or does not match the description. Contact <a href="mailto:support@3dify.dev">support@3dify.dev</a> within 14 days.</p>

<h2>5. User conduct</h2>
<ul>
    <li>Do not upload, share or trade content that infringes third-party rights.</li>
    <li>Do not attempt to hack, DDoS or scrape the Platform.</li>
    <li>Do not use bots for data collection, fake ratings or review fraud.</li>
</ul>

<h2>6. Intellectual property</h2>
<p>Author-created content belongs to the authors. The 3Dify brand, UI design, page copy, logo and brand colours belong to the Platform.</p>

<h2>7. Liability</h2>
<p>The Platform is provided "as is". We do not guarantee uninterrupted operation and are not liable for damages arising from print failures, wrong settings, hardware incompatibility or third-party actions.</p>

<h2>8. Changes</h2>
<p>We may update these Terms. Users are notified by email or via a banner. Continued use after publication of a new version means acceptance.</p>

<h2>9. Jurisdiction and contacts</h2>
<p>These Terms are governed by the law of Ukraine. Disputes are settled in courts at the Platform location. Legal contact — <a href="mailto:legal@3dify.dev">legal@3dify.dev</a>.</p>
HTML,
            ],
            [
                'slug' => 'contact',
                'locale' => 'en',
                'title' => 'Contact',
                'subtitle' => 'Fastest ways to reach the 3Dify team.',
                'meta_description' => 'Email, address, social media and contact form for the 3Dify marketplace team.',
                'sort_order' => 40,
                'body' => <<<'HTML'
<h2>Email</h2>
<ul>
    <li><strong>General:</strong> <a href="mailto:info@3dify.dev">info@3dify.dev</a></li>
    <li><strong>Buyer support:</strong> <a href="mailto:support@3dify.dev">support@3dify.dev</a></li>
    <li><strong>Authors and payouts:</strong> <a href="mailto:authors@3dify.dev">authors@3dify.dev</a></li>
    <li><strong>Legal, DMCA:</strong> <a href="mailto:legal@3dify.dev">legal@3dify.dev</a></li>
    <li><strong>Partnerships and media:</strong> <a href="mailto:partners@3dify.dev">partners@3dify.dev</a></li>
</ul>

<h2>Working hours</h2>
<p>Monday — Friday, 9:00–18:00 (Europe/Kyiv). Tickets received outside these hours are answered the next business day.</p>

<h2>How to get a quick reply</h2>
<ol>
    <li>Describe the issue clearly: what happens vs. what should happen.</li>
    <li>Attach order ID, screenshots, and page URL.</li>
    <li>Mention your browser / OS for technical issues.</li>
</ol>

<h2>Company details</h2>
<p>3Dify Marketplace<br>
Kyiv, Ukraine<br>
Tax / EDRPOU number — to be added once registration is finalised.</p>

<h2>Social media</h2>
<p>Follow new releases:</p>
<ul>
    <li>Telegram channel — model launches</li>
    <li>X / Twitter — short updates</li>
    <li>YouTube — guides and reviews</li>
</ul>
HTML,
            ],
            [
                'slug' => 'faq',
                'locale' => 'en',
                'title' => 'Frequently Asked Questions',
                'subtitle' => 'Most common questions from buyers and authors — with quick answers.',
                'meta_description' => '3Dify FAQ: how to buy, how to download, how to print STL/OBJ/3MF, author payouts.',
                'sort_order' => 50,
                'body' => <<<'HTML'
<h2>For buyers</h2>

<details open>
    <summary><strong>What format will I receive?</strong></summary>
    <p>Depending on the model, it is STL, OBJ, 3MF, GLB or a ZIP archive with files and instructions. All formats work in popular slicers (Cura, PrusaSlicer, Bambu Studio, Lychee).</p>
</details>

<details>
    <summary><strong>How do I download a purchased file?</strong></summary>
    <p>Go to <em>Dashboard → My purchases</em>. Each order has a <strong>Download</strong> button. The link is signed and expires after 24 hours; it regenerates automatically.</p>
</details>

<details>
    <summary><strong>Can I refund if I don’t like the file?</strong></summary>
    <p>Digital goods are not refundable after download. Exceptions — corrupted file or mismatch with the description. Email <a href="mailto:support@3dify.dev">support@3dify.dev</a>.</p>
</details>

<details>
    <summary><strong>Can I print this model for sale?</strong></summary>
    <p>Yes — if you bought a commercial license. Personal license is for personal printing only.</p>
</details>

<h2>For authors</h2>

<details>
    <summary><strong>How do I become an author and upload a model?</strong></summary>
    <p>Register, click "Become an author", set up your payout details, then click "Upload model". Your first publication goes through manual review.</p>
</details>

<details>
    <summary><strong>What commission does 3Dify take?</strong></summary>
    <p>Default 15% per sale. Top authors and exclusive releases get reduced rates.</p>
</details>

<details>
    <summary><strong>How are payouts processed?</strong></summary>
    <p>Monthly to bank account, Wise or crypto wallet. Minimum payout is 800 UAH. Submit the request from your author dashboard.</p>
</details>

<details>
    <summary><strong>What if my model gets copied?</strong></summary>
    <p>Send a DMCA request to <a href="mailto:legal@3dify.dev">legal@3dify.dev</a> with originals and links. Review takes up to 5 business days.</p>
</details>

<h2>Technical questions</h2>

<details>
    <summary><strong>Best formats for 3D printing?</strong></summary>
    <p>FDM/SLA — STL or 3MF. If colour data matters (e.g. Bambu AMS) — pick 3MF. For browser preview — GLB.</p>
</details>

<details>
    <summary><strong>The site feels slow. What should I do?</strong></summary>
    <p>Check connection, clear browser cache or try incognito. If it persists — email us with browser/OS and a Network panel screenshot.</p>
</details>
HTML,
            ],
            [
                'slug' => 'privacy',
                'locale' => 'en',
                'title' => 'Privacy Policy',
                'subtitle' => 'What data we collect, why, and how we protect it.',
                'meta_description' => '3Dify privacy policy: collection, processing and protection of personal data under GDPR and Ukrainian law.',
                'sort_order' => 60,
                'body' => <<<'HTML'
<h2>1. Data controller</h2>
<p>The controller of your personal data is 3Dify Marketplace. Reach us at <a href="mailto:privacy@3dify.dev">privacy@3dify.dev</a> with privacy questions.</p>

<h2>2. Data we collect</h2>
<ul>
    <li><strong>Account:</strong> name, email, password (stored as a hash), nickname.</li>
    <li><strong>Payments:</strong> processed by Stripe, AIFO, Wayforpay. We do not store full card numbers.</li>
    <li><strong>Technical:</strong> IP, browser, OS, refresh-token, device fingerprint — for security and bot protection.</li>
    <li><strong>User content:</strong> models, previews, descriptions, reviews, support messages.</li>
    <li><strong>Cookies:</strong> session (auth) and analytics (Google Analytics, with consent).</li>
</ul>

<h2>3. Legal bases</h2>
<ul>
    <li>Contract — to provide the marketplace services.</li>
    <li>Consent — for marketing emails and analytics cookies.</li>
    <li>Legitimate interest — for security, fraud detection, service improvement.</li>
    <li>Legal obligations — for accounting and tax requirements.</li>
</ul>

<h2>4. Who we share data with</h2>
<p>We may share data with:</p>
<ul>
    <li>Payment providers (for processing payments).</li>
    <li>Email provider Amazon SES (for transactional emails).</li>
    <li>CDN and hosting partners (for delivery).</li>
    <li>Google analytics services (anonymised).</li>
</ul>
<p>We never sell your data to third parties.</p>

<h2>5. Retention</h2>
<ul>
    <li>Account data — while the account is active + 12 months after deletion.</li>
    <li>Payment documents — 7 years (accounting law).</li>
    <li>Technical logs — up to 90 days.</li>
    <li>Cookies — from session up to 13 months.</li>
</ul>

<h2>6. Your rights</h2>
<p>Under GDPR and the Ukrainian personal data law you have the right to:</p>
<ul>
    <li>Receive a copy of your data.</li>
    <li>Correct inaccuracies.</li>
    <li>Erase data ("right to be forgotten") when there is no legal ground to keep it.</li>
    <li>Restrict and object to processing.</li>
    <li>Port data to another service.</li>
</ul>
<p>Send requests to <a href="mailto:privacy@3dify.dev">privacy@3dify.dev</a> from the email tied to your account.</p>

<h2>7. Security</h2>
<p>We use HTTPS, store passwords as bcrypt hashes, regularly update dependencies and run audits. Card data never touches our servers — it is processed by PCI DSS-certified providers.</p>

<h2>8. Cookies</h2>
<p>On first visit we display a consent banner. You may pick "necessary only" or accept analytics and marketing. You can change preferences any time via the footer link.</p>

<h2>9. Changes</h2>
<p>We may update this policy. The last update date is shown at the bottom. We notify users about significant changes by email and on the site.</p>
HTML,
            ],
        ];
    }
}
