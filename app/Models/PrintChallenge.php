<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Model;

class PrintChallenge extends Model
{
    use HasLocalizedFields;

    protected $fillable = [
        'slug', 'title', 'description', 'cover_path', 'prize_product_id',
        'prize_description', 'starts_at', 'ends_at', 'is_active',
    ];

    protected $casts = [
        'title'       => 'array',
        'description' => 'array',
        'starts_at'   => 'datetime',
        'ends_at'     => 'datetime',
        'is_active'   => 'boolean',
    ];

    public function entries()
    {
        return $this->hasMany(PrintChallengeEntry::class, 'challenge_id');
    }

    public function isOpen(): bool
    {
        if (! $this->is_active) return false;
        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) return false;
        if ($this->ends_at && $now->gt($this->ends_at)) return false;
        return true;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
