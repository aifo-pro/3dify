<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Advertisement extends Model
{
    use HasLocalizedFields;

    public const TYPES = ['grid', 'banner', 'sidebar'];

    public const PAGES = ['catalog', 'category', 'home', 'search'];

    protected $fillable = [
        'title', 'description', 'image_path', 'target_url', 'badge_label',
        'ad_type', 'grid_every', 'pages', 'is_active',
        'starts_at', 'ends_at', 'created_by',
    ];

    protected $casts = [
        'title'       => 'array',
        'description' => 'array',
        'pages'       => 'array',
        'is_active'   => 'boolean',
        'starts_at'   => 'datetime',
        'ends_at'     => 'datetime',
        'impressions' => 'integer',
        'clicks'      => 'integer',
        'grid_every'  => 'integer',
    ];

    /** Active ad running right now. */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    /** Active ads for a specific page. */
    public function scopeForPage(Builder $query, string $page): Builder
    {
        return $query->active()->where(function ($q) use ($page) {
            $q->whereNull('pages')
              ->orWhereJsonContains('pages', $page);
        });
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) return null;
        return Storage::disk('public')->url($this->image_path);
    }

    public function ctr(): float
    {
        if ($this->impressions === 0) return 0.0;
        return round($this->clicks / $this->impressions * 100, 2);
    }

    public function isExpired(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }
}
