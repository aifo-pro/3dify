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
