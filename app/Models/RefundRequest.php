<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_REFUNDED = 'refunded';

    public const REASONS = [
        'corrupted_files' => 'Файли пошкоджені або некоректні',
        'misleading' => 'Не відповідає опису',
        'duplicate' => 'Дубльовано іншу мою покупку',
        'cant_print' => 'Не друкується (помилки геометрії)',
        'other' => 'Інше',
    ];

    protected $fillable = [
        'order_id', 'user_id', 'reason', 'message', 'status', 'admin_notes', 'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function balanceTransactions()
    {
        return $this->hasMany(AccountBalanceTransaction::class);
    }
}
