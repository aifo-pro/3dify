<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PromoCodeAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = PromoCode::query()->latest();

        if ($q = trim((string) $request->input('q', ''))) {
            $query->where('code', 'like', '%'.$q.'%');
        }
        if ($request->input('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->input('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $codes = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => PromoCode::count(),
            'active' => PromoCode::where('is_active', true)->count(),
            'used' => PromoCode::sum('used_count'),
        ];

        return view('admin.promo-codes', compact('codes', 'stats'));
    }

    public function store(Request $request, AuditLogger $audit)
    {
        $data = $this->validated($request);
        $data['code'] = strtoupper($data['code']);
        $promo = PromoCode::create($data);
        $audit->record('promo.create', $promo, ['code' => $promo->code]);

        return back()->with('status', __('Промокод створено.'));
    }

    public function update(Request $request, PromoCode $promoCode, AuditLogger $audit)
    {
        $data = $this->validated($request, $promoCode->id);
        $data['code'] = strtoupper($data['code']);
        $promoCode->update($data);
        $audit->record('promo.update', $promoCode, ['code' => $promoCode->code, 'is_active' => $promoCode->is_active]);

        return back()->with('status', __('Промокод оновлено.'));
    }

    public function destroy(PromoCode $promoCode, AuditLogger $audit)
    {
        $audit->record('promo.delete', $promoCode, ['code' => $promoCode->code]);
        $promoCode->delete();
        return back()->with('status', __('Промокод видалено.'));
    }

    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:60', Rule::unique('promo_codes', 'code')->ignore($id)],
            'description' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
