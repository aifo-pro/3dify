<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RefundRequest;
use App\Services\AccountBalanceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RefundAdminController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');

        $query = RefundRequest::query()->with(['user', 'order.items.product'])->latest();
        if (in_array($status, ['pending', 'approved', 'rejected', 'refunded'], true)) {
            $query->where('status', $status);
        }

        $requests = $query->paginate(20)->withQueryString();

        $counts = [
            'all' => RefundRequest::count(),
            'pending' => RefundRequest::where('status', 'pending')->count(),
            'approved' => RefundRequest::where('status', 'approved')->count(),
            'rejected' => RefundRequest::where('status', 'rejected')->count(),
            'refunded' => RefundRequest::where('status', 'refunded')->count(),
        ];

        return view('admin.refunds', [
            'requests' => $requests,
            'counts' => $counts,
            'status' => $status,
            'reasons' => RefundRequest::REASONS,
        ]);
    }

    public function update(Request $request, RefundRequest $refundRequest, \App\Services\AuditLogger $audit, AccountBalanceService $balances)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected', 'refunded'])],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $refundRequest->update([
            'status' => $data['status'],
            'admin_notes' => $data['admin_notes'] ?? $refundRequest->admin_notes,
            'processed_at' => in_array($data['status'], ['approved', 'rejected', 'refunded'], true) ? now() : null,
        ]);

        // If marked refunded, also flip the order status.
        if ($data['status'] === 'refunded' && $refundRequest->order) {
            $refundRequest->order->update(['status' => 'refunded']);
            $balances->creditRefund($refundRequest->fresh(['order']));
        }

        $audit->record('refund.update', $refundRequest, ['status' => $data['status']]);

        return back()->with('status', __('Статус заявки оновлено.'));
    }
}
