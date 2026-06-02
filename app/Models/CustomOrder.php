<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CustomOrder extends Model
{
    use SoftDeletes;

    public const TYPE_MODEL_CREATION = 'model_creation';
    public const TYPE_PRINT_SERVICE = 'print_service';

    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_DISCUSSING = 'discussing';
    public const STATUS_WAITING_BUYER_ACCEPT = 'waiting_buyer_accept';
    public const STATUS_WAITING_PAYMENT = 'waiting_payment';
    public const STATUS_PAID = 'paid';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_DISPUTED = 'disputed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    public const STATUSES = [
        self::STATUS_PENDING_REVIEW,
        self::STATUS_DISCUSSING,
        self::STATUS_WAITING_BUYER_ACCEPT,
        self::STATUS_WAITING_PAYMENT,
        self::STATUS_PAID,
        self::STATUS_IN_PROGRESS,
        self::STATUS_SHIPPED,
        self::STATUS_DELIVERED,
        self::STATUS_COMPLETED,
        self::STATUS_DISPUTED,
        self::STATUS_CANCELLED,
        self::STATUS_REFUNDED,
    ];

    protected $fillable = [
        'number',
        'buyer_id',
        'author_id',
        'category_id',
        'type',
        'status',
        'title',
        'description',
        'budget_amount',
        'budget_is_negotiable',
        'deadline_at',
        'quantity',
        'dimensions',
        'material',
        'color',
        'delivery_service',
        'delivery_city',
        'delivery_city_ref',
        'delivery_warehouse_ref',
        'delivery_address',
        'delivery_selected_at',
        'extra_comment',
        'price',
        'currency',
        'delivery_days',
        'offer_description',
        'offer_terms',
        'escrow_amount',
        'platform_fee_amount',
        'author_amount',
        'accepted_at',
        'paid_at',
        'started_at',
        'shipped_at',
        'delivered_at',
        'completed_at',
        'cancelled_at',
        'disputed_at',
        'auto_complete_at',
        'metadata',
    ];

    protected $casts = [
        'budget_amount' => 'decimal:2',
        'budget_is_negotiable' => 'boolean',
        'deadline_at' => 'date',
        'price' => 'decimal:2',
        'escrow_amount' => 'decimal:2',
        'platform_fee_amount' => 'decimal:2',
        'author_amount' => 'decimal:2',
        'accepted_at' => 'datetime',
        'delivery_selected_at' => 'datetime',
        'paid_at' => 'datetime',
        'started_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'disputed_at' => 'datetime',
        'auto_complete_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $order): void {
            if (! $order->number) {
                $order->number = 'CUS-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5));
            }
        });
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function messages()
    {
        return $this->hasMany(CustomOrderMessage::class)->oldest();
    }

    public function files()
    {
        return $this->hasMany(CustomOrderFile::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(CustomOrderStatusLog::class)->latest();
    }

    public function payments()
    {
        return $this->hasMany(CustomOrderPayment::class);
    }

    public function milestones()
    {
        return $this->hasMany(CustomOrderMilestone::class)->orderBy('sort_order');
    }

    public function shipments()
    {
        return $this->hasMany(CustomOrderShipment::class);
    }

    public function disputes()
    {
        return $this->hasMany(CustomOrderDispute::class);
    }

    public function isParticipant(?User $user): bool
    {
        return $user && in_array($user->id, [$this->buyer_id, $this->author_id], true);
    }

    public function canBePaid(): bool
    {
        if ($this->status !== self::STATUS_WAITING_PAYMENT || (float) $this->price <= 0) {
            return false;
        }

        return $this->isModelCreation() || $this->hasDeliverySelection();
    }

    public function isDownloadOrWorkLocked(): bool
    {
        return in_array($this->status, [self::STATUS_CANCELLED, self::STATUS_REFUNDED, self::STATUS_DISPUTED], true);
    }

    public function isModelCreation(): bool
    {
        return $this->type === self::TYPE_MODEL_CREATION;
    }

    public function isPrintService(): bool
    {
        return $this->type === self::TYPE_PRINT_SERVICE;
    }

    public function hasDeliverySelection(): bool
    {
        return $this->isPrintService()
            && filled($this->delivery_service)
            && filled($this->delivery_city)
            && filled($this->delivery_address);
    }

    public function resultFiles()
    {
        return $this->files()->where('purpose', 'result');
    }

    public function statusLabel(): string
    {
        return __('custom_orders.statuses.'.$this->status);
    }

    public function typeLabel(): string
    {
        return __('custom_orders.types.'.$this->type);
    }
}
