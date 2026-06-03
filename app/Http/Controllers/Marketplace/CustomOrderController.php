<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomOrderDeliveryRequest;
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
use App\Services\AifoPaymentService;
use App\Services\CustomOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        $message = $orders->message($customOrder, $request->user(), $request->validated('body'), is_array($files) ? $files : [$files]);

        if ($request->expectsJson()) {
            $message->load(['user', 'files']);

            return response()->json([
                'message' => $this->serializeMessage($customOrder, $message, $request->user()),
            ], 201);
        }

        return back()->with('status', __('custom_orders.message_sent'));
    }

    public function messages(Request $request, CustomOrder $customOrder)
    {
        $this->ensureParticipant($customOrder, $request->user());

        $after = max(0, (int) $request->query('after', 0));

        $messages = $customOrder->messages()
            ->with(['user', 'files'])
            ->when($after > 0, fn ($query) => $query->where('id', '>', $after))
            ->oldest()
            ->get()
            ->map(fn ($message) => $this->serializeMessage($customOrder, $message, $request->user()))
            ->values();

        return response()->json(['messages' => $messages]);
    }

    public function offer(CustomOrderOfferRequest $request, CustomOrder $customOrder, CustomOrderService $orders)
    {
        abort_unless($customOrder->author_id === $request->user()->id || $request->user()->canModerate(), 403);
        abort_unless(in_array($customOrder->status, [
            CustomOrder::STATUS_PENDING_REVIEW,
            CustomOrder::STATUS_DISCUSSING,
            CustomOrder::STATUS_WAITING_BUYER_ACCEPT,
        ], true), 422);

        $orders->offer($customOrder, $request->user(), $request->validated());

        return back()->with('status', __('custom_orders.offer_sent'));
    }

    public function accept(Request $request, CustomOrder $customOrder, CustomOrderService $orders)
    {
        abort_unless($customOrder->buyer_id === $request->user()->id, 403);

        if ($customOrder->isPrintService() && ! $customOrder->hasDeliverySelection()) {
            return back()->withErrors(['delivery_address' => __('custom_orders.errors.delivery_required_before_accept')]);
        }

        $orders->acceptOffer($customOrder, $request->user());

        return back()->with('status', __('custom_orders.offer_accepted'));
    }

    public function delivery(CustomOrderDeliveryRequest $request, CustomOrder $customOrder, CustomOrderService $orders)
    {
        abort_unless($customOrder->buyer_id === $request->user()->id, 403);

        $orders->selectDelivery($customOrder, $request->user(), $request->validated());

        return back()->with('status', __('custom_orders.delivery.saved'));
    }

    public function payRedirect(Request $request, CustomOrder $customOrder)
    {
        $user = $request->user();

        if ($customOrder->isParticipant($user) || $user?->canModerate()) {
            return redirect()
                ->route('custom-orders.show', $customOrder)
                ->with('error', __('custom_orders.errors.payment_post_required'));
        }

        return redirect()
            ->route('custom-orders.index')
            ->with('error', __('custom_orders.errors.wrong_account'));
    }

    public function pay(Request $request, CustomOrder $customOrder, AifoPaymentService $payments)
    {
        $user = $request->user();

        abort_unless($user && $customOrder->buyer_id === $user->id, 403);

        if (! $customOrder->canBePaid()) {
            return redirect()
                ->route('custom-orders.show', $customOrder)
                ->withErrors(['payment' => __('custom_orders.errors.payment_not_available')]);
        }

        try {
            $payment = $payments->createCustomOrderPayment($customOrder);
        } catch (\Throwable $e) {
            Log::error('custom_order.aifo_checkout_failed', [
                'custom_order_id' => $customOrder->id,
                'buyer_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->route('custom-orders.show', $customOrder)
                ->withErrors(['payment' => __('custom_orders.errors.payment_checkout_unavailable')]);
        }

        $checkoutUrl = $payment?->payload['checkout_url'] ?? null;

        if (! is_string($checkoutUrl) || trim($checkoutUrl) === '') {
            return redirect()
                ->route('custom-orders.show', $customOrder)
                ->withErrors(['payment' => __('custom_orders.errors.payment_checkout_unavailable')]);
        }

        return redirect()->away($checkoutUrl);
    }

    public function demoPay(Request $request, CustomOrder $customOrder, AifoPaymentService $payments)
    {
        return $this->pay($request, $customOrder, $payments);
    }

    public function cancel(Request $request, CustomOrder $customOrder, CustomOrderService $orders)
    {
        $this->ensureParticipant($customOrder, $request->user());

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $orders->cancel($customOrder, $request->user(), $validated['reason'] ?? null);

        return back()->with('status', __('custom_orders.cancelled'));
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

    private function serializeMessage(CustomOrder $order, $message, User $viewer): array
    {
        return [
            'id' => $message->id,
            'own' => $message->user_id === $viewer->id,
            'author' => $message->user?->displayName() ?: __('custom_orders.system'),
            'body' => $message->body,
            'created_at' => $message->created_at?->translatedFormat('d M H:i'),
            'files' => $message->files->map(fn ($file) => [
                'id' => $file->id,
                'name' => $file->original_name,
                'url' => route('custom-orders.files.download', [$order, $file]),
            ])->values()->all(),
        ];
    }
}
