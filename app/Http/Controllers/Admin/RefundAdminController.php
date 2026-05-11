<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RefundRequest;
use App\Services\AccountBalanceService;
use App\Services\RefundEvidenceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RefundAdminController extends Controller
{
    public function index(Request $request, RefundEvidenceService $evidence)
    {
        $status = $request->input('status', 'pending');

        $query = RefundRequest::query()->with(['user', 'order.items.product.files'])->latest();
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
            'evidence' => $requests->getCollection()
                ->mapWithKeys(fn (RefundRequest $refund) => [$refund->id => $evidence->forRequest($refund)]),
        ]);
    }

    public function update(Request $request, RefundRequest $refundRequest, \App\Services\AuditLogger $audit, AccountBalanceService $balances)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected', 'refunded'])],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $finalStatus = $data['status'] === RefundRequest::STATUS_APPROVED
            ? RefundRequest::STATUS_REFUNDED
            : $data['status'];

        $refundRequest->update([
            'status' => $finalStatus,
            'admin_notes' => $data['admin_notes'] ?? $refundRequest->admin_notes,
            'processed_at' => in_array($finalStatus, ['rejected', 'refunded'], true) ? now() : null,
        ]);

        // A confirmed refund is completed inside 3Dify balance: block downloads and credit the buyer.
        if ($finalStatus === RefundRequest::STATUS_REFUNDED && $refundRequest->order) {
            $refundRequest->order->update(['status' => 'refunded']);
            $balances->creditRefund($refundRequest->fresh(['order']));
        }

        $audit->record('refund.update', $refundRequest, ['status' => $finalStatus]);

        return back()->with('status', __('Статус заявки оновлено.'));
    }
}
