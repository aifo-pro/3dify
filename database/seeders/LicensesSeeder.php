<?php

namespace Database\Seeders;

use App\Models\License;
use Illuminate\Database\Seeder;

/**
 * Idempotent seeder that ensures the canonical license catalog exists and is
 * tagged with badge labels, colors and icons used by the UI components.
 *
 * Existing rows are upgraded (only the new flag/badge fields are touched).
 * Names and descriptions of pre-existing rows are NOT overwritten so admin
 * edits and translations are preserved.
 */
class LicensesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->licenses() as $row) {
            $existing = License::query()->where('slug', $row['slug'])->first();

            if ($existing) {
                $existing->fill([
                    'badge_label' => $row['badge_label'],
                    'badge_color' => $row['badge_color'],
                    'icon_slug' => $row['icon_slug'],
                    'allows_commercial_use' => $row['allows_commercial_use'],
                    'requires_attribution' => $row['requires_attribution'],
                    'allows_redistribution' => $row['allows_redistribution'],
                    'allows_remix' => $row['allows_remix'],
                    'allows_selling_prints' => $row['allows_selling_prints'],
                    'forbids_file_resale' => $row['forbids_file_resale'],
                ]);

                if (empty($existing->name) || $existing->name === [] || $existing->name === ['uk' => '', 'en' => '']) {
                    $existing->name = $row['name'];
                }
                if (empty($existing->description) || $existing->description === [] || $existing->description === ['uk' => '', 'en' => '']) {
                    $existing->description = $row['description'];
                }

                $existing->save();
            } else {
                License::create($row);
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function licenses(): array
    {
        return [
            [
                'slug' => 'personal',
                'name' => ['uk' => 'Особисте використання', 'en' => 'Personal use'],
                'description' => [
                    'uk' => 'Для особистого друку без перепродажу файлів та без комерційного використання.',
                    'en' => 'For personal printing only — no file resale and no commercial use.',
                ],
                'badge_label' => 'Personal',
                'badge_color' => 'sky',
                'icon_slug' => 'personal',
                'allows_commercial_use' => false,
                'requires_attribution' => false,
                'allows_redistribution' => false,
                'allows_remix' => true,
                'allows_selling_prints' => false,
                'forbids_file_resale' => true,
            ],
            [
                'slug' => 'commercial',
                'name' => ['uk' => 'Комерційна ліцензія', 'en' => 'Commercial license'],
                'description' => [
                    'uk' => 'Дозволяє продавати надруковані копії моделі. Перепродаж самого файла заборонено.',
                    'en' => 'Allows selling printed copies of the model. Reselling the source file is forbidden.',
                ],
                'badge_label' => 'Commercial',
                'badge_color' => 'emerald',
                'icon_slug' => 'commercial',
                'allows_commercial_use' => true,
                'requires_attribution' => false,
                'allows_redistribution' => false,
                'allows_remix' => true,
                'allows_selling_prints' => true,
                'forbids_file_resale' => true,
            ],
            [
                'slug' => 'royalty-free',
                'name' => ['uk' => 'Royalty-free', 'en' => 'Royalty-free'],
                'description' => [
                    'uk' => 'Одна оплата — необмежене використання у комерційних та особистих проєктах.',
                    'en' => 'Pay once, use forever in personal and commercial projects.',
                ],
                'badge_label' => 'Royalty-free',
                'badge_color' => 'violet',
                'icon_slug' => 'royalty-free',
                'allows_commercial_use' => true,
                'requires_attribution' => false,
                'allows_redistribution' => false,
                'allows_remix' => true,
                'allows_selling_prints' => true,
                'forbids_file_resale' => true,
            ],
            [
                'slug' => 'attribution',
                'name' => ['uk' => 'Із зазначенням автора', 'en' => 'Attribution required'],
                'description' => [
                    'uk' => 'Можна використовувати, якщо вказати ім’я автора в описі або супровідних матеріалах.',
                    'en' => 'May be used as long as the original author is credited.',
                ],
                'badge_label' => 'Attribution',
                'badge_color' => 'amber',
                'icon_slug' => 'attribution',
                'allows_commercial_use' => true,
                'requires_attribution' => true,
                'allows_redistribution' => false,
                'allows_remix' => true,
                'allows_selling_prints' => true,
                'forbids_file_resale' => true,
            ],
            [
                'slug' => 'no-redistribution',
                'name' => ['uk' => 'Без поширення', 'en' => 'No redistribution'],
                'description' => [
                    'uk' => 'Файл не можна публікувати на інших сайтах, у Telegram-каналах чи торентах.',
                    'en' => 'The file may not be re-uploaded to other sites, Telegram channels or torrents.',
                ],
                'badge_label' => 'No redistribution',
                'badge_color' => 'rose',
                'icon_slug' => 'no-redistribution',
                'allows_commercial_use' => false,
                'requires_attribution' => false,
                'allows_redistribution' => false,
                'allows_remix' => false,
                'allows_selling_prints' => false,
                'forbids_file_resale' => true,
            ],
            [
                'slug' => 'free',
                'name' => ['uk' => 'Безкоштовна', 'en' => 'Free'],
                'description' => [
                    'uk' => 'Безкоштовне завантаження для всіх. Зберігайте авторство під час показів.',
                    'en' => 'Free download for everyone — please credit the author when sharing.',
                ],
                'badge_label' => 'Free',
                'badge_color' => 'emerald',
                'icon_slug' => 'free',
                'allows_commercial_use' => false,
                'requires_attribution' => true,
                'allows_redistribution' => false,
                'allows_remix' => true,
                'allows_selling_prints' => false,
                'forbids_file_resale' => true,
            ],
            [
                'slug' => 'premium',
                'name' => ['uk' => 'Преміальна ліцензія', 'en' => 'Premium license'],
                'description' => [
                    'uk' => 'Розширені права для команд та виробництв: великий тираж, ексклюзивний дизайн, сапорт.',
                    'en' => 'Extended rights for teams and production: high-volume use, exclusive design, support.',
                ],
                'badge_label' => 'Premium',
                'badge_color' => 'fuchsia',
                'icon_slug' => 'premium',
                'allows_commercial_use' => true,
                'requires_attribution' => false,
                'allows_redistribution' => false,
                'allows_remix' => true,
                'allows_selling_prints' => true,
                'forbids_file_resale' => true,
            ],
            [
                'slug' => 'non-commercial',
                'name' => ['uk' => 'Некомерційна', 'en' => 'Non-commercial'],
                'description' => [
                    'uk' => 'Лише некомерційне використання. Продаж надрукованих копій заборонено.',
                    'en' => 'Non-commercial use only — selling printed copies is forbidden.',
                ],
                'badge_label' => 'Non-commercial',
                'badge_color' => 'zinc',
                'icon_slug' => 'non-commercial',
                'allows_commercial_use' => false,
                'requires_attribution' => true,
                'allows_redistribution' => false,
                'allows_remix' => true,
                'allows_selling_prints' => false,
                'forbids_file_resale' => true,
            ],
            [
                'slug' => 'remix-allowed',
                'name' => ['uk' => 'Дозволено реміксувати', 'en' => 'Remix allowed'],
                'description' => [
                    'uk' => 'Можна модифікувати, поєднувати з іншими моделями і публікувати ремікс під цією ж ліцензією.',
                    'en' => 'You may modify the model, combine it with others and publish derivatives under the same license.',
                ],
                'badge_label' => 'Remix OK',
                'badge_color' => 'cyan',
                'icon_slug' => 'remix-allowed',
                'allows_commercial_use' => true,
                'requires_attribution' => true,
                'allows_redistribution' => false,
                'allows_remix' => true,
                'allows_selling_prints' => true,
                'forbids_file_resale' => true,
            ],
            [
                'slug' => 'remix-forbidden',
                'name' => ['uk' => 'Без реміксу', 'en' => 'Remix forbidden'],
                'description' => [
                    'uk' => 'Модель не можна змінювати чи комбінувати з іншими моделями.',
                    'en' => 'The model may not be modified or combined with other works.',
                ],
                'badge_label' => 'No remix',
                'badge_color' => 'lime',
                'icon_slug' => 'remix-forbidden',
                'allows_commercial_use' => false,
                'requires_attribution' => false,
                'allows_redistribution' => false,
                'allows_remix' => false,
                'allows_selling_prints' => false,
                'forbids_file_resale' => true,
            ],
        ];
    }
}
