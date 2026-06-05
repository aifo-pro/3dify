<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\PromoCodeService;
use Illuminate\Http\Request;

class PromoCodeController extends Controller
{
    public function apply(Request $request, Product $product, PromoCodeService $service)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:60'],
        ]);

        $result = $service->validate($data['code'], $request->user(), (float) $product->price, $product);

        if (! $result) {
            return back()->withErrors(['promo' => __('Промокод неактивний, прострочений або вже використовувався вами.')]);
        }

        // Stash for the upcoming checkout request (cleared after redemption).
        session()->put('promo.'.$product->id, [
            'code' => $result['promo']->code,
            'discount' => (float) $result['discount'],
            'product_id' => $product->id,
        ]);

        return back()->with('status', __('Промокод застосовано: −:amount EUR.', ['amount' => number_format((float) $result['discount'], 2)]));
    }
}
