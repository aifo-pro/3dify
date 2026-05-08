<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class FeaturedProductsController extends Controller
{
    public function index()
    {
        $featured = Product::where('is_featured', true)
            ->with('author')
            ->orderByRaw('featured_order IS NULL')
            ->orderBy('featured_order')
            ->orderByDesc('updated_at')
            ->get();

        $candidates = Product::where('is_featured', false)
            ->where('status', 'published')
            ->with('author')
            ->orderByDesc('created_at')
            ->limit(40)
            ->get();

        return view('admin.featured.index', compact('featured', 'candidates'));
    }

    public function toggle(Product $product, AuditLogger $audit)
    {
        $product->update(['is_featured' => ! $product->is_featured]);
        $audit->record('product.featured.toggle', $product, ['is_featured' => $product->is_featured]);
        return back()->with('status', $product->is_featured ? __('Додано до Featured.') : __('Прибрано з Featured.'));
    }

    public function reorder(Request $request, AuditLogger $audit)
    {
        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer'],
        ]);
        foreach ($data['order'] as $index => $id) {
            Product::where('id', $id)->update(['featured_order' => $index + 1]);
        }
        $audit->record('product.featured.reorder', null, ['count' => count($data['order'])]);
        return response()->json(['ok' => true]);
    }
}
