<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Support\Seo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_has_organization_and_website_jsonld(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('"@type":"Organization"', false)
            ->assertSee('"@type":"WebSite"', false)
            ->assertSee('"SearchAction"', false)
            ->assertSee('<meta name="robots"', false);
    }

    public function test_sitemap_index_lists_subsitemaps(): void
    {
        $res = $this->get('/sitemap.xml');
        $res->assertOk();
        $res->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $res->assertSee('sitemapindex', false);
        $res->assertSee('sitemap-models.xml', false);
        $res->assertSee('sitemap-authors.xml', false);
        $res->assertSee('sitemap-categories.xml', false);
    }

    public function test_per_type_sitemaps_render(): void
    {
        $this->get('/sitemap-pages.xml')->assertOk()->assertSee('<urlset', false);
        $this->get('/sitemap-models.xml')->assertOk()->assertSee('<urlset', false);
        $this->get('/sitemap-categories.xml')->assertOk()->assertSee('<urlset', false);
        $this->get('/sitemap-authors.xml')->assertOk()->assertSee('<urlset', false);
    }

    public function test_author_page_has_person_and_breadcrumb_schema(): void
    {
        $author = User::factory()->create(['role' => 'author', 'username' => 'designer-jane']);
        Product::query()->create([
            'user_id' => $author->id, 'slug' => 'seo-model',
            'title' => ['uk' => 'SEO model', 'en' => 'SEO model'],
            'description' => ['uk' => 'D', 'en' => 'D'],
            'status' => 'published', 'price' => 0, 'currency' => 'UAH',
            'is_free' => true, 'published_at' => now(),
        ]);

        $this->get(route('authors.show', ['user' => 'designer-jane']))
            ->assertOk()
            ->assertSee('"@type":"Person"', false)
            ->assertSee('"@type":"ProfilePage"', false)
            ->assertSee('"@type":"BreadcrumbList"', false);
    }

    public function test_category_page_has_collectionpage_schema_and_canonical(): void
    {
        $category = Category::query()->create([
            'slug' => 'gadgets',
            'name' => ['uk' => 'Гаджети', 'en' => 'Gadgets'],
            'description' => ['uk' => 'Корисні моделі', 'en' => 'Useful models'],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->get(route('categories.show', $category))
            ->assertOk()
            ->assertSee('"@type":"CollectionPage"', false)
            ->assertSee('rel="canonical" href="'.route('categories.show', $category).'"', false);
    }

    public function test_home_renders_faqpage_schema(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('"@type":"FAQPage"', false)
            ->assertSee('"@type":"Question"', false);
    }

    public function test_product_page_has_softwareapplication_schema(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $product = Product::query()->create([
            'user_id' => $author->id, 'slug' => 'sw-model',
            'title' => ['uk' => 'SW model', 'en' => 'SW model'],
            'short_description' => ['uk' => 'Short', 'en' => 'Short'],
            'description' => ['uk' => 'Desc', 'en' => 'Desc'],
            'status' => 'published', 'price' => 100, 'personal_price' => 100,
            'currency' => 'UAH', 'is_free' => false, 'published_at' => now(),
        ]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('SoftwareApplication', false)
            ->assertSee('DesignApplication', false);
    }

    public function test_product_page_has_faqpage_and_visible_breadcrumbs(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $product = Product::query()->create([
            'user_id' => $author->id, 'slug' => 'faq-model',
            'title' => ['uk' => 'FAQ model', 'en' => 'FAQ model'],
            'short_description' => ['uk' => 'Short', 'en' => 'Short'],
            'description' => ['uk' => 'Desc', 'en' => 'Desc'],
            'status' => 'published', 'price' => 100, 'personal_price' => 100,
            'currency' => 'UAH', 'is_free' => false, 'published_at' => now(),
        ]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('FAQPage', false)
            ->assertSee('aria-label="Breadcrumb"', false);
    }

    public function test_seo_helper_faq_and_breadcrumb_shape(): void
    {
        $faq = Seo::faqPage([['question' => 'Q1', 'answer' => 'A1']]);
        $this->assertSame('FAQPage', $faq['@type']);
        $this->assertSame('Q1', $faq['mainEntity'][0]['name']);

        $crumb = Seo::breadcrumb([['name' => 'Home', 'url' => 'https://x/'], ['name' => 'Cat', 'url' => 'https://x/cat']]);
        $this->assertSame(2, $crumb['itemListElement'][1]['position']);
    }
}
