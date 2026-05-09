<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = ['order_id', 'product_id', 'author_id', 'price', 'currency', 'license_type', 'license_snapshot'];

    protected $casts = [
        'price' => 'decimal:2',
        'license_snapshot' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
