<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductReport extends Model
{
    public const STATUSES = ['pending', 'reviewed', 'dismissed', 'actioned'];

    public const REASONS = [
        'broken_files' => 'Файли не відкриваються / биті',
        'wrong_license' => 'Невідповідна ліцензія',
        'copyright' => 'Скарга на авторські права',
        'misleading' => 'Опис не відповідає моделі',
        'inappropriate' => 'Неприйнятний контент',
        'other' => 'Інше',
    ];

    protected $fillable = ['product_id', 'user_id', 'reason', 'message', 'status', 'admin_notes', 'reviewed_at'];

    protected $casts = ['reviewed_at' => 'datetime'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
