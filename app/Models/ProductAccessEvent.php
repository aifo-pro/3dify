<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAccessEvent extends Model
{
    public const EVENT_DOWNLOAD = 'download';
    public const EVENT_SIGNED_DOWNLOAD = 'signed_download';
    public const EVENT_SLICER_OPEN = 'slicer_open';
    public const EVENT_PRINT_PROFILE_DOWNLOAD = 'print_profile_download';
    public const EVENT_DOWNLOAD_MODAL_OPEN = 'download_modal_open';

    protected $fillable = [
        'user_id',
        'product_id',
        'model_file_id',
        'order_id',
        'event',
        'target',
        'ip_address',
        'user_agent',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function file()
    {
        return $this->belongsTo(ModelFile::class, 'model_file_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
