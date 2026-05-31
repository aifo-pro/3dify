<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductBundle;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BundleAdminController extends Controller
{
    public function index()
    {
        $bundles = ProductBundle::with('author')->withCount('items')->latest()->paginate(20);
        return view('admin.bundles.index', compact('bundles'));
    }

    public function create()
    {
        $products = Product::query()->where('status', 'published')->with('author')->orderBy('title->uk')->get();
        return view('admin.bundles.form', ['bundle' => new ProductBundle, 'products' => $products]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $bundle = ProductBundle::create($data);
        $this->syncItems($bundle, $request);
        return redirect()->route('admin.bundles.index')->with('status', 'Bundle created.');
    }

    public function edit(ProductBundle $bundle)
    {
        $products = Product::query()->where('status', 'published')->with('author')->orderBy('title->uk')->get();
        $bundle->loadMissing('items');
        return view('admin.bundles.form', compact('bundle', 'products'));
    }

    public function update(Request $request, ProductBundle $bundle)
    {
        $bundle->update($this->validated($request));
        $this->syncItems($bundle, $request);
        return back()->with('status', 'Bundle updated.');
    }

    public function destroy(ProductBundle $bundle)
    {
        $bundle->delete();
        return redirect()->route('admin.bundles.index')->with('status', 'Bundle deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title_uk'         => ['required', 'string', 'max:200'],
            'title_en'         => ['nullable', 'string', 'max:200'],
            'description_uk'   => ['nullable', 'string', 'max:2000'],
            'description_en'   => ['nullable', 'string', 'max:2000'],
            'price'            => ['required', 'numeric', 'min:0'],
            'discount_percent' => ['required', 'integer', 'min:0', 'max:99'],
            'is_active'        => ['nullable'],
            'cover'            => ['nullable', 'image', 'max:4096'],
        ]);

        $slug = $request->input('slug') ?: Str::slug($data['title_uk']).'-'.Str::lower(Str::random(5));

        return [
            'slug'             => $slug,
            'title'            => ['uk' => $data['title_uk'], 'en' => $data['title_en'] ?: $data['title_uk']],
            'description'      => ['uk' => $data['description_uk'] ?? '', 'en' => $data['description_en'] ?? $data['description_uk'] ?? ''],
            'price'            => $data['price'],
            'currency'         => 'UAH',
            'discount_percent' => $data['discount_percent'],
            'is_active'        => $request->boolean('is_active'),
            'user_id'          => auth()->id(),
        ];
    }

    private function syncItems(ProductBundle $bundle, Request $request): void
    {
        $ids = array_filter((array) $request->input('product_ids', []));
        $sync = [];
        foreach ($ids as $i => $id) {
            $sync[(int)$id] = ['sort_order' => $i];
        }
        $bundle->items()->sync($sync);
    }
}
