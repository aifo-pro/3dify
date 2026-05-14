<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogTaxonomyController extends Controller
{
    public function categories()
    {
        return view('admin.blog.categories', ['categories' => BlogCategory::orderBy('sort_order')->get()]);
    }

    public function storeCategory(Request $request)
    {
        BlogCategory::create($this->categoryData($request));

        return back()->with('status', __('blog.admin.category_saved'));
    }

    public function updateCategory(Request $request, BlogCategory $category)
    {
        $category->update($this->categoryData($request, $category));

        return back()->with('status', __('blog.admin.category_updated'));
    }

    public function destroyCategory(BlogCategory $category)
    {
        $category->delete();

        return back()->with('status', __('blog.admin.category_deleted'));
    }

    public function tags()
    {
        return view('admin.blog.tags', ['tags' => BlogTag::orderBy('name_uk')->get()]);
    }

    public function storeTag(Request $request)
    {
        BlogTag::create($this->tagData($request));

        return back()->with('status', __('blog.admin.tag_saved'));
    }

    public function updateTag(Request $request, BlogTag $tag)
    {
        $tag->update($this->tagData($request, $tag));

        return back()->with('status', __('blog.admin.tag_updated'));
    }

    public function destroyTag(BlogTag $tag)
    {
        $tag->delete();

        return back()->with('status', __('blog.admin.tag_deleted'));
    }

    private function categoryData(Request $request, ?BlogCategory $category = null): array
    {
        $data = $request->validate([
            'name_uk' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'alpha_dash', 'max:255', Rule::unique('blog_categories', 'slug')->ignore($category?->id)],
            'description_uk' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'seo_title_uk' => ['nullable', 'string', 'max:255'],
            'seo_title_en' => ['nullable', 'string', 'max:255'],
            'seo_description_uk' => ['nullable', 'string', 'max:500'],
            'seo_description_en' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name_en'] ?: Str::transliterate($data['name_uk']));
        // Unchecked checkbox is omitted; must not default to true on update.
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }

    private function tagData(Request $request, ?BlogTag $tag = null): array
    {
        $data = $request->validate([
            'name_uk' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'alpha_dash', 'max:255', Rule::unique('blog_tags', 'slug')->ignore($tag?->id)],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name_en'] ?: Str::transliterate($data['name_uk']));
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
