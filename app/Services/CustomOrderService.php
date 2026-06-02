<?php

namespace App\Services;

use App\Models\AccountBalanceTransaction;
use App\Models\CustomOrder;
use App\Models\CustomOrderFile;
use App\Models\CustomOrderMessage;
use App\Models\CustomOrderMilestone;
use App\Models\CustomOrderShipment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CustomOrderService
{
    public const PLATFORM_FEE_PERCENT = 10.0;

    public function create(User $buyer, array $data, array $files = []): CustomOrder
    {
        return DB::transaction(function () use ($buyer, $data, $files) {
            $data = [
                ...$data,
                'delivery_service' => null,
                'delivery_city' => null,
                'delivery_city_ref' => null,
                'delivery_warehouse_ref' => null,
                'delivery_address' => null,
                'delivery_selected_at' => null,
            ];

            if (($data['type'] ?? CustomOrder::TYPE_MODEL_CREATION) === CustomOrder::TYPE_MODEL_CREATION) {
                $data = [
                    ...$data,
                    'quantity' => null,
                    'dimensions' => null,
                    'material' => null,
                    'color' => null,
                    'extra_comment' => null,
                ];
            }

            $order = CustomOrder::create([
                ...$data,
                'buyer_id' => $buyer->id,
                'currency' => 'UAH',
                'status' => CustomOrder::STATUS_PENDING_REVIEW,
            ]);

            $this->log($order, $buyer, null, CustomOrder::STATUS_PENDING_REVIEW, __('custom_orders.logs.created'));

            if (! empty($data['description'])) {
                $message = $order->messages()->create([
                    'user_id' => $buyer->id,
                    'role' => 'buyer',
                    'body' => $data['description'],
                ]);

                $this->storeFiles($order, $buyer, $files, 'brief', $message);
            }

            return $order;
        });
    }

    public function message(CustomOrder $order, User $user, ?string $body, array $files = []): CustomOrderMessage
    {
        return DB::transaction(function () use ($order, $user, $body, $files) {
            $message = $order->messages()->create([
                'user_id' => $user->id,
                'role' => $this->roleFor($order, $user),
                'body' => $body,
            ]);

            $this->storeFiles($order, $user, $files, 'attachment', $message);

            if ($order->status === CustomOrder::STATUS_PENDING_REVIEW && $order->author_id) {
                $this->transition($order, CustomOrder::STATUS_DISCUSSING, $user, __('custom_orders.logs.discussion_started'));
            }

            return $message;
        });
    }

    public function offer(CustomOrder $order, User $author, array $data): CustomOrder
    {
        return DB::transaction(function () use ($order, $author, $data) {
            $price = round((float) $data['price'], 2);
            $fee = round($price * (self::PLATFORM_FEE_PERCENT / 100), 2);

            $order->fill([
                'author_id' => $order->author_id ?: $author->id,
                'price' => $price,
                'currency' => 'UAH',
                'delivery_days' => $data['delivery_days'] ?? null,
                'offer_description' => $data['offer_description'] ?? null,
                'offer_terms' => $data['offer_terms'] ?? null,
                'escrow_amount' => $price,
                'platform_fee_amount' => $fee,
                'author_amount' => max(0, round($price - $fee, 2)),
            ])->save();

            $this->syncMilestones($order, $data['milestones'] ?? []);
            $this->transition($order, CustomOrder::STATUS_WAITING_BUYER_ACCEPT, $author, __('custom_orders.logs.offer_sent'));

            return $order->fresh(['milestones']);
        });
    }

    public function acceptOffer(CustomOrder $order, User $buyer): CustomOrder
    {
        return DB::transaction(function () use ($order, $buyer) {
            if ($order->isPrintService()) {
                throw_unless($order->hasDeliverySelection(), \InvalidArgumentException::class, 'Delivery branch is required before accepting a print order offer.');
            }

            $order->forceFill(['accepted_at' => now()])->save();
            $this->transition($order, CustomOrder::STATUS_WAITING_PAYMENT, $buyer, __('custom_orders.logs.offer_accepted'));

            return $order;
        });
    }

    public function selectDelivery(CustomOrder $order, User $buyer, array $data): CustomOrder
    {
        return DB::transaction(function () use ($order, $buyer, $data) {
            throw_unless($order->isPrintService(), \InvalidArgumentException::class, 'Delivery selection is available only for print orders.');
            throw_unless($buyer->id === $order->buyer_id, \InvalidArgumentException::class, 'Only the buyer can select delivery.');
            throw_unless(in_array($order->status, [CustomOrder::STATUS_WAITING_BUYER_ACCEPT, CustomOrder::STATUS_WAITING_PAYMENT], true), \InvalidArgumentException::class, 'Delivery cannot be changed at this stage.');

            $order->forceFill([
                'delivery_service' => $data['delivery_service'],
                'delivery_city' => $data['delivery_city'],
                'delivery_city_ref' => $data['delivery_city_ref'] ?? null,
                'delivery_warehouse_ref' => $data['delivery_warehouse_ref'] ?? null,
                'delivery_address' => $data['delivery_address'],
                'extra_comment' => $data['extra_comment'] ?? null,
                'delivery_selected_at' => now(),
            ])->save();

            $this->log($order, $buyer, $order->status, $order->status, __('custom_orders.logs.delivery_selected'), [
                'carrier' => $data['delivery_service'],
                'city' => $data['delivery_city'],
                'warehouse' => $data['delivery_address'],
            ]);

            return $order->fresh();
        });
    }

    public function markPaid(CustomOrder $order, User $actor, ?string $providerPaymentId = null, array $payload = []): CustomOrder
    {
        return DB::transaction(function () use ($order, $actor, $providerPaymentId, $payload) {
            $order->payments()->create([
                'provider' => 'aifo',
                'provider_payment_id' => $providerPaymentId,
                'status' => 'paid',
                'amount' => $order->price,
                'currency' => 'UAH',
                'paid_at' => now(),
                'payload' => $payload,
            ]);

            $order->forceFill(['paid_at' => now()])->save();
            $this->transition($order, CustomOrder::STATUS_PAID, $actor, __('custom_orders.logs.paid_escrow'));
            $this->transition($order, CustomOrder::STATUS_IN_PROGRESS, $actor, __('custom_orders.logs.started'));

            return $order;
        });
    }

    public function ship(CustomOrder $order, User $author, array $data): CustomOrderShipment
    {
        return DB::transaction(function () use ($order, $author, $data) {
            throw_unless($order->isPrintService(), \InvalidArgumentException::class, 'Only print orders can be shipped.');

            $shipment = $order->shipments()->create([
                'carrier' => $data['carrier'] ?? null,
                'tracking_number' => $data['tracking_number'] ?? null,
                'status' => 'tracking_added',
                'shipped_at' => now(),
            ]);

            $this->transition($order, CustomOrder::STATUS_SHIPPED, $author, __('custom_orders.logs.shipped'));

            return $shipment;
        });
    }

    public function sendModelResult(CustomOrder $order, User $author, ?string $comment, array $files = []): CustomOrder
    {
        return DB::transaction(function () use ($order, $author, $comment, $files) {
            throw_unless($order->isModelCreation(), \InvalidArgumentException::class, 'Only model creation orders can receive digital result files.');

            $message = $order->messages()->create([
                'user_id' => $author->id,
                'role' => $this->roleFor($order, $author),
                'body' => $comment ?: __('custom_orders.result.default_message'),
            ]);

            $this->storeFiles($order, $author, $files, 'result', $message);

            $order->forceFill([
                'delivered_at' => now(),
                'auto_complete_at' => now()->addDays((int) config('custom_orders.auto_complete_days', 7)),
            ])->save();

            $this->transition($order, CustomOrder::STATUS_DELIVERED, $author, __('custom_orders.logs.result_sent'));

            return $order->fresh(['files', 'messages.files']);
        });
    }

    public function markDelivered(CustomOrder $order, User $actor): CustomOrder
    {
        return DB::transaction(function () use ($order, $actor) {
            $order->forceFill([
                'delivered_at' => now(),
                'auto_complete_at' => now()->addDays((int) config('custom_orders.auto_complete_days', 7)),
            ])->save();

            $this->transition($order, CustomOrder::STATUS_DELIVERED, $actor, __('custom_orders.logs.delivered'));

            return $order;
        });
    }

    public function complete(CustomOrder $order, User $buyer): CustomOrder
    {
        return DB::transaction(function () use ($order, $buyer) {
            if ($order->isModelCreation()) {
                throw_unless($order->resultFiles()->exists(), \InvalidArgumentException::class, 'Model result files are required before completion.');
                throw_unless($order->status === CustomOrder::STATUS_DELIVERED, \InvalidArgumentException::class, 'Model result must be delivered before completion.');
            }

            if ($order->isPrintService()) {
                throw_unless($order->status === CustomOrder::STATUS_DELIVERED, \InvalidArgumentException::class, 'Print order must be delivered before completion.');
            }

            $order->forceFill(['completed_at' => now()])->save();
            $this->transition($order, CustomOrder::STATUS_COMPLETED, $buyer, __('custom_orders.logs.completed'));
            $this->releaseEscrow($order);

            return $order;
        });
    }

    public function dispute(CustomOrder $order, User $user, array $data): CustomOrder
    {
        return DB::transaction(function () use ($order, $user, $data) {
            $order->disputes()->create([
                'opened_by' => $user->id,
                'reason' => $data['reason'],
                'description' => $data['description'],
                'status' => 'open',
            ]);

            $order->forceFill(['disputed_at' => now()])->save();
            $this->transition($order, CustomOrder::STATUS_DISPUTED, $user, __('custom_orders.logs.disputed'));

            return $order;
        });
    }

    public function transition(CustomOrder $order, string $status, ?User $actor = null, ?string $note = null, array $metadata = []): void
    {
        $from = $order->status;
        if ($from === $status) {
            return;
        }

        $order->forceFill(['status' => $status])->save();
        $this->log($order, $actor, $from, $status, $note, $metadata);
    }

    public function storeFiles(CustomOrder $order, User $user, array $files, string $purpose, ?CustomOrderMessage $message = null): void
    {
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store('custom-orders/'.$order->id, 'public');

            CustomOrderFile::create([
                'custom_order_id' => $order->id,
                'message_id' => $message?->id,
                'user_id' => $user->id,
                'purpose' => $purpose,
                'disk' => 'public',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize() ?: 0,
            ]);
        }
    }

    private function syncMilestones(CustomOrder $order, array $milestones): void
    {
        $order->milestones()->delete();

        foreach (array_values(array_filter($milestones)) as $idx => $title) {
            CustomOrderMilestone::create([
                'custom_order_id' => $order->id,
                'title' => (string) $title,
                'sort_order' => $idx + 1,
                'status' => 'pending',
            ]);
        }
    }

    private function releaseEscrow(CustomOrder $order): void
    {
        if (! Schema::hasTable('account_balance_transactions') || ! $order->author_id || (float) $order->author_amount <= 0) {
            return;
        }

        AccountBalanceTransaction::firstOrCreate(
            [
                'user_id' => $order->author_id,
                'type' => AccountBalanceTransaction::TYPE_CREDIT,
                'description' => __('custom_orders.balance_release', ['number' => $order->number]),
            ],
            [
                'status' => AccountBalanceTransaction::STATUS_SETTLED,
                'amount' => $order->author_amount,
                'currency' => 'UAH',
                'metadata' => ['source' => 'custom_order', 'custom_order_id' => $order->id],
            ]
        );
    }

    private function log(CustomOrder $order, ?User $actor, ?string $from, string $to, ?string $note = null, array $metadata = []): void
    {
        $order->statusLogs()->create([
            'user_id' => $actor?->id,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
            'metadata' => $metadata ?: null,
        ]);
    }

    private function roleFor(CustomOrder $order, User $user): string
    {
        if ($user->id === $order->buyer_id) {
            return 'buyer';
        }

        if ($user->id === $order->author_id) {
            return 'author';
        }

        return $user->canModerate() ? 'admin' : 'participant';
    }
}
