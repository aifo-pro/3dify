<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomOrderDisputeRequest;
use App\Http\Requests\CustomOrderMessageRequest;
use App\Http\Requests\CustomOrderOfferRequest;
use App\Http\Requests\CustomOrderResultRequest;
use App\Http\Requests\CustomOrderShipmentRequest;
use App\Http\Requests\CustomOrderStoreRequest;
use App\Models\Category;
use App\Models\CustomOrder;
use App\Models\CustomOrderFile;
use App\Models\User;
use App\Services\CustomOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CustomOrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $scope = $request->string('scope')->toString();

        $orders = CustomOrder::query()
            ->with(['buyer', 'author'])
            ->when($scope === 'author', fn ($q) => $q->where('author_id', $user->id))
            ->when($scope !== 'author', fn ($q) => $q->where(function ($qq) use ($user) {
                $qq->where('buyer_id', $user->id)->orWhere('author_id', $user->id);
            }))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('marketplace.custom-orders.index', compact('orders', 'scope'));
    }

    public function create(Request $request)
    {
        $author = null;
        if ($request->filled('author')) {
            $author = User::query()
                ->where('id', $request->input('author'))
                ->orWhere('username', $request->input('author'))
                ->first();
        }

        $categories = collect();
        if (Schema::hasTable('categories')) {
            $query = Category::query();

            if (Schema::hasColumn('categories', 'is_active')) {
                $query->where('is_active', true);
            }

            if (Schema::hasColumn('categories', 'sort_order')) {
                $query->orderBy('sort_order');
            }

            $categories = $query->orderBy('slug')->get();
        }

        return view('marketplace.custom-orders.create', compact('author', 'categories'));
    }

    public function store(CustomOrderStoreRequest $request, CustomOrderService $orders)
    {
        $data = $request->validated();
        $files = $request->file('files', []);
        $data['budget_is_negotiable'] = (bool) ($data['budget_is_negotiable'] ?? true);

        $order = $orders->create($request->user(), $data, is_array($files) ? $files : [$files]);

        return redirect()->route('custom-orders.show', $order)->with('status', __('custom_orders.created'));
    }

    public function show(Request $request, CustomOrder $customOrder)
    {
        $this->ensureParticipant($customOrder, $request->user());

        $customOrder->load([
            'buyer',
            'author',
            'category',
            'messages.user',
            'messages.files',
            'files',
            'milestones',
            'shipments.events',
            'disputes.opener',
            'statusLogs.user',
        ]);

        return view('marketplace.custom-orders.show', ['order' => $customOrder]);
    }

    public function message(CustomOrderMessageRequest $request, CustomOrder $customOrder, CustomOrderService $orders)
    {
        $this->ensureParticipant($customOrder, $request->user());

        $files = $request->file('files', []);
        $orders->message($customOrder, $request->user(), $request->validated('body'), is_array($files) ? $files : [$files]);

        return back()->with('status', __('custom_orders.message_sent'));
    }

    public function offer(CustomOrderOfferRequest $request, CustomOrder $customOrder, CustomOrderService $orders)
    {
        abort_unless($customOrder->author_id === $request->user()->id || $request->user()->canModerate(), 403);

        $orders->offer($customOrder, $request->user(), $request->validated());

        return back()->with('status', __('custom_orders.offer_sent'));
    }

    public function accept(Request $request, CustomOrder $customOrder, CustomOrderService $orders)
    {
        abort_unless($customOrder->buyer_id === $request->user()->id, 403);

        $orders->acceptOffer($customOrder, $request->user());

        return back()->with('status', __('custom_orders.offer_accepted'));
    }

    public function demoPay(Request $request, CustomOrder $customOrder, CustomOrderService $orders)
    {
        abort_unless($customOrder->buyer_id === $request->user()->id || $request->user()->canModerate(), 403);
        abort_unless($customOrder->canBePaid(), 422);

        $orders->markPaid($customOrder, $request->user(), 'manual-'.$customOrder->number, ['source' => 'manual_custom_order']);

        return back()->with('status', __('custom_orders.paid'));
    }

    public function ship(CustomOrderShipmentRequest $request, CustomOrder $customOrder, CustomOrderService $orders)
    {
        abort_unless($customOrder->author_id === $request->user()->id || $request->user()->canModerate(), 403);

        $orders->ship($customOrder, $request->user(), $request->validated());

        return back()->with('status', __('custom_orders.shipment_saved'));
    }

    public function result(CustomOrderResultRequest $request, CustomOrder $customOrder, CustomOrderService $orders)
    {
        abort_unless($customOrder->author_id === $request->user()->id || $request->user()->canModerate(), 403);
        abort_unless($customOrder->isModelCreation(), 404);
        abort_unless(in_array($customOrder->status, [CustomOrder::STATUS_PAID, CustomOrder::STATUS_IN_PROGRESS, CustomOrder::STATUS_DELIVERED], true), 422);

        $files = $request->file('result_files', []);
        $orders->sendModelResult($customOrder, $request->user(), $request->validated('result_comment'), is_array($files) ? $files : [$files]);

        return back()->with('status', __('custom_orders.result.sent'));
    }

    public function downloadFile(Request $request, CustomOrder $customOrder, CustomOrderFile $file)
    {
        $this->ensureParticipant($customOrder, $request->user());
        abort_unless($file->custom_order_id === $customOrder->id, 404);
        abort_if($customOrder->isDownloadOrWorkLocked(), 403);

        return Storage::disk($file->disk ?: 'public')->download($file->path, $file->original_name);
    }

    public function complete(Request $request, CustomOrder $customOrder, CustomOrderService $orders)
    {
        abort_unless($customOrder->buyer_id === $request->user()->id, 403);
        abort_unless($customOrder->status === CustomOrder::STATUS_DELIVERED, 422);

        $orders->complete($customOrder, $request->user());

        return back()->with('status', __('custom_orders.completed'));
    }

    public function dispute(CustomOrderDisputeRequest $request, CustomOrder $customOrder, CustomOrderService $orders)
    {
        $this->ensureParticipant($customOrder, $request->user());

        $orders->dispute($customOrder, $request->user(), $request->validated());

        return back()->with('status', __('custom_orders.disputed'));
    }

    private function ensureParticipant(CustomOrder $order, User $user): void
    {
        abort_unless($order->isParticipant($user) || $user->canModerate(), 403);
    }
}
