<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class CompareController extends Controller
{
    public function show(Request $request)
    {
        $ids = collect(explode(',', $request->input('ids', '')))
            ->map('intval')
            ->filter()
            ->unique()
            ->take(3)
            ->values();

        $products = $ids->isNotEmpty()
            ? Product::query()
                ->with(['author', 'category', 'license', 'files', 'reviews'])
                ->published()
                ->whereIn('id', $ids)
                ->get()
                ->sortBy(fn ($p) => $ids->search($p->id))
                ->values()
            : collect();

        return view('marketplace.compare', compact('products', 'ids'));
    }

    public function add(Request $request, Product $product)
    {
        $current = collect(explode(',', $request->query('current', '')))
            ->map('intval')
            ->filter()
            ->unique()
            ->values();

        if (! $current->contains($product->id) && $current->count() < 3) {
            $current->push($product->id);
        }

        return redirect()->route('compare', ['ids' => $current->join(',')]);
    }
}
