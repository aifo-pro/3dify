<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrinterProfile extends Model
{
    public const TECHNOLOGIES = ['fdm' => 'FDM (нитка)', 'sla' => 'SLA (смола)', 'mjf' => 'MJF / SLS'];

    protected $fillable = [
        'user_id', 'name', 'technology',
        'bed_x', 'bed_y', 'bed_z',
        'nozzle', 'materials', 'is_default',
    ];

    protected $casts = [
        'materials' => 'array',
        'is_default' => 'boolean',
        'nozzle' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Returns null when product has no declared dimensions; otherwise true/false.
     */
    public function fits(Product $product): ?bool
    {
        if (! $product->dim_x && ! $product->dim_y && ! $product->dim_z) {
            return null;
        }
        if (! $this->bed_x || ! $this->bed_y) {
            return null;
        }
        $dx = (int) ($product->dim_x ?? 0);
        $dy = (int) ($product->dim_y ?? 0);
        $dz = (int) ($product->dim_z ?? 0);

        $fitsXY = ($dx <= $this->bed_x && $dy <= $this->bed_y) || ($dx <= $this->bed_y && $dy <= $this->bed_x);
        $fitsZ = $this->bed_z ? $dz <= $this->bed_z : true;
        return $fitsXY && $fitsZ;
    }
}
