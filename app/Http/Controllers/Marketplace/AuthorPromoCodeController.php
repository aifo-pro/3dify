<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AuthorPromoCodeController extends Controller
{
    public const MAX_ACTIVE_CODES = 30;

    public function index(Request $request)
    {
        $codes = PromoCode::query()
            ->where('author_id', $request->user()->id)
            ->withCount('redemptions')
            ->latest()
            ->paginate(20);

        return view('marketplace.author.promo-codes', compact('codes'));
    }

    public function store(Request $request)
    {
        $author = $request->user();

        abort_unless(
            PromoCode::where('author_id', $author->id)->count() < self::MAX_ACTIVE_CODES,
            422,
            __('author_promo.errors.limit_reached')
        );

        $data = $request->validate([
            'code' => [
                'required', 'string', 'min:3', 'max:40',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('promo_codes', 'code'),
            ],
            'value' => ['required', 'integer', 'min:1', 'max:90'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ], [], [
            'code' => __('author_promo.code'),
            'value' => __('author_promo.percent'),
        ]);

        PromoCode::create([
            'author_id' => $author->id,
            'code' => strtoupper($data['code']),
            'type' => PromoCode::TYPE_PERCENT,
            'value' => $data['value'],
            'currency' => 'UAH',
            'usage_limit' => $data['usage_limit'] ?? null,
            'used_count' => 0,
            'expires_at' => $data['expires_at'] ?? null,
            'is_active' => true,
            'description' => __('author_promo.author_code_desc', ['author' => $author->displayName()]),
        ]);

        return back()->with('status', __('author_promo.created'));
    }

    public function toggle(Request $request, PromoCode $promoCode)
    {
        abort_unless($promoCode->author_id === $request->user()->id, 403);

        $promoCode->update(['is_active' => ! $promoCode->is_active]);

        return back()->with('status', __('author_promo.updated'));
    }

    public function destroy(Request $request, PromoCode $promoCode)
    {
        abort_unless($promoCode->author_id === $request->user()->id, 403);

        $promoCode->delete();

        return back()->with('status', __('author_promo.deleted'));
    }
}
