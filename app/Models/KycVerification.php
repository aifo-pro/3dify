<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KycVerification extends Model
{
    public const PROVIDER_DIDIT = 'didit';

    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_FAILED = 'failed';

    public const STATUSES = [
        self::STATUS_NOT_STARTED,
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_EXPIRED,
        self::STATUS_FAILED,
    ];

    protected $fillable = [
        'user_id',
        'provider',
        'provider_session_id',
        'provider_applicant_id',
        'status',
        'decision',
        'rejection_reason',
        'verification_url',
        'webhook_payload',
        'approved_at',
        'rejected_at',
        'expired_at',
    ];

    protected $casts = [
        'webhook_payload' => 'array',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}
