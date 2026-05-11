<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\RefundRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RefundRequestController extends Controller
{
    public function index(Request $request)
    {
        $requests = RefundRequest::query()
            ->where('user_id', $request->user()->id)
            ->with(['order.items.product.files', 'balanceTransactions'])
            ->latest()
            ->paginate(15);

        return view('marketplace.refunds.index', [
            'requests' => $requests,
            'reasons' => RefundRequest::REASONS,
        ]);
    }

    public function store(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        // Only paid orders within 14 days are eligible.
        if ($order->status !== 'paid') {
            return back()->withErrors(['order' => __('Повернути можна лише оплачене замовлення.')]);
        }
        $paidAt = $order->updated_at ?? $order->created_at;
        if ($paidAt && $paidAt->diffInDays(now()) > 14) {
            return back()->withErrors(['order' => __('Повернення можливе протягом 14 днів після оплати.')]);
        }

        $exists = RefundRequest::query()
            ->where('order_id', $order->id)
            ->whereIn('status', [
                RefundRequest::STATUS_PENDING,
                RefundRequest::STATUS_APPROVED,
                RefundRequest::STATUS_REFUNDED,
            ])
            ->exists();
        if ($exists) {
            return back()->withErrors(['order' => __('Заявка на повернення вже існує.')]);
        }

        $data = $request->validate([
            'reason' => ['required', Rule::in(array_keys(RefundRequest::REASONS))],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        RefundRequest::create([
            'order_id' => $order->id,
            'user_id' => $request->user()->id,
            'reason' => $data['reason'],
            'message' => $data['message'] ?? null,
            'status' => RefundRequest::STATUS_PENDING,
        ]);

        return redirect()->route('refunds.index')->with('status', __('Заявку відправлено на розгляд.'));
    }
}
