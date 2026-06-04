<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomOrder;
use App\Services\CustomOrderService;
use App\Services\ParcelTrackingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomOrderAdminController extends Controller
{
    public function index(Request $request)
    {
        $orders = CustomOrder::query()
            ->with(['buyer', 'author'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->input('q').'%';
                $q->where(function ($qq) use ($term) {
                    $qq->where('number', 'like', $term)->orWhere('title', 'like', $term);
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.custom-orders.index', compact('orders'));
    }

    public function show(CustomOrder $customOrder)
    {
        $customOrder->load(['buyer', 'author', 'messages.user', 'files', 'milestones', 'shipments.events', 'disputes.opener', 'statusLogs.user']);

        return view('admin.custom-orders.show', ['order' => $customOrder]);
    }

    public function update(Request $request, CustomOrder $customOrder, CustomOrderService $orders)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(CustomOrder::STATUSES)],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $orders->transition($customOrder, $data['status'], $request->user(), $data['note'] ?? null);

        return back()->with('status', __('custom_orders.status'));
    }

    /**
     * Resolve a disputed order: refund the buyer, release escrow to the author, or split.
     */
    public function resolveDispute(Request $request, CustomOrder $customOrder, CustomOrderService $orders)
    {
        $data = $request->validate([
            'outcome' => ['required', Rule::in([
                CustomOrderService::RESOLVE_REFUND_BUYER,
                CustomOrderService::RESOLVE_PARTIAL,
                CustomOrderService::RESOLVE_RELEASE_AUTHOR,
            ])],
            'refund_amount' => ['nullable', 'numeric', 'min:0', 'max:'.((float) $customOrder->price)],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['outcome'] === CustomOrderService::RESOLVE_PARTIAL
            && (float) ($data['refund_amount'] ?? 0) <= 0) {
            return back()->withErrors(['refund_amount' => __('custom_orders.dispute.partial_requires_amount')]);
        }

        try {
            $orders->resolveDispute(
                $customOrder,
                $request->user(),
                $data['outcome'],
                isset($data['refund_amount']) ? (float) $data['refund_amount'] : null,
                $data['note'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['dispute' => __('custom_orders.dispute.not_resolvable')]);
        }

        return back()->with('status', __('custom_orders.dispute.resolved'));
    }

    /**
     * Manually re-poll the carrier for every shipment of this order.
     */
    public function track(CustomOrder $customOrder, ParcelTrackingService $tracker, CustomOrderService $orders)
    {
        $customOrder->load('shipments');

        foreach ($customOrder->shipments as $shipment) {
            try {
                $update = $tracker->track($shipment);
                if ($update) {
                    $orders->applyTracking($shipment, $update);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return back()->with('status', __('custom_orders.tracking_refreshed'));
    }
}
