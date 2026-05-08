<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PayoutAdminController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');

        $payouts = Payout::query()
            ->with('author')
            ->when(in_array($status, Payout::STATUSES, true), fn ($q) => $q->where('status', $status))
            ->latest('requested_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.payouts', [
            'payouts' => $payouts,
            'status' => $status,
            'counts' => [
                'all' => Payout::count(),
                'pending' => Payout::where('status', 'pending')->count(),
                'approved' => Payout::where('status', 'approved')->count(),
                'paid' => Payout::where('status', 'paid')->count(),
                'rejected' => Payout::where('status', 'rejected')->count(),
            ],
            'totals' => [
                'pending' => (float) Payout::where('status', 'pending')->sum('amount'),
                'paid' => (float) Payout::where('status', 'paid')->sum('amount'),
            ],
        ]);
    }

    public function update(Request $request, Payout $payout, AuditLogger $audit)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(Payout::STATUSES)],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $payout->status = $data['status'];
        $payout->admin_notes = $data['admin_notes'] ?? $payout->admin_notes;
        if (in_array($data['status'], ['paid', 'rejected'], true) && ! $payout->processed_at) {
            $payout->processed_at = now();
        }
        $payout->save();

        $audit->record('payout.update', $payout, ['status' => $data['status']]);

        return back()->with('status', __('Заявку оновлено.'));
    }
}
