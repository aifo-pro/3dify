<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    public const STATUSES = ['pending', 'approved', 'paid', 'rejected'];

    public const METHODS = [
        'bank_transfer' => 'Банківський переказ',
        'paypal' => 'PayPal',
        'usdt_trc20' => 'USDT (TRC20)',
        'card' => 'Картка',
    ];

    protected $fillable = [
        'author_id', 'amount', 'currency', 'status', 'method',
        'details', 'admin_notes', 'requested_at', 'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
