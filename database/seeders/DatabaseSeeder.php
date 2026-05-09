<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\EmailTemplate;
use App\Models\License;
use App\Models\Product;
use App\Models\SeoPage;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => '3Dify Admin',
            'username' => 'admin',
            'email' => 'admin@3dify.local',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $author = User::factory()->create([
            'name' => '3Dify Maker',
            'username' => 'maker',
            'email' => 'maker@3dify.local',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'author',
        ]);

        $categories = collect([
            ['miniatures', 'Мініатюри', 'Miniatures'],
            ['tools', 'Інструменти', 'Tools'],
            ['home', 'Дім', 'Home'],
            ['toys', 'Іграшки', 'Toys'],
        ])->map(fn ($row, $i) => Category::create([
            'slug' => $row[0],
            'name' => ['uk' => $row[1], 'en' => $row[2]],
            'description' => ['uk' => 'Добірка 3D-моделей', 'en' => 'Curated 3D models'],
            'sort_order' => $i,
        ]));

        $tags = collect(['stl', 'printer-ready', 'decor', 'engineering'])->map(fn ($slug) => Tag::create([
            'slug' => $slug,
            'name' => ['uk' => $slug, 'en' => $slug],
        ]));

        $license = License::firstOrCreate(
            ['slug' => 'personal'],
            [
                'name' => ['uk' => 'Персональна ліцензія', 'en' => 'Personal license'],
                'description' => ['uk' => 'Для особистого друку без перепродажу файлів.', 'en' => 'For personal printing without file resale.'],
            ]
        );

        $product = Product::create([
            'user_id' => $author->id,
            'category_id' => $categories->first()->id,
            'license_id' => $license->id,
            'slug' => 'demo-calibration-dragon',
            'title' => ['uk' => 'Демо калібрувальний дракон', 'en' => 'Demo calibration dragon'],
            'short_description' => ['uk' => 'Тестова модель для перевірки каталогу.', 'en' => 'Demo model for marketplace testing.'],
            'description' => ['uk' => 'Ця модель показує сторінку продукту, оплату, захист файлів і viewer.', 'en' => 'This model demonstrates product pages, payment, protected files and viewer.'],
            'status' => 'published',
            'price' => 0,
            'currency' => 'EUR',
            'is_free' => true,
            'is_featured' => true,
            'published_at' => now(),
        ]);
        $product->tags()->sync($tags->pluck('id'));

        Setting::upsert([
            ['group' => 'site', 'key' => 'site.name', 'value' => json_encode('3Dify'), 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'payments', 'key' => 'payments.aifo_merchant_id', 'value' => json_encode('demo'), 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'brand', 'key' => 'brand.logo_path', 'value' => null, 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'brand', 'key' => 'brand.favicon_path', 'value' => null, 'created_at' => now(), 'updated_at' => now()],
        ], ['key'], ['group', 'value', 'updated_at']);

        SeoPage::create(['route_name' => 'home', 'locale' => 'uk', 'title' => '3Dify - маркетплейс 3D-моделей', 'description' => 'Публікуйте, купуйте та завантажуйте моделі для 3D-друку.']);

        EmailTemplate::insert([
            ['key' => 'purchase_success', 'locale' => 'uk', 'subject' => 'Ваше замовлення 3Dify', 'body' => 'Дякуємо за покупку.', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'model_sold', 'locale' => 'uk', 'subject' => 'Новий продаж', 'body' => 'Вашу модель купили.', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->call([
            LicensesSeeder::class,
            LegalPagesSeeder::class,
        ]);

        $admin->products()->count();
    }
}
