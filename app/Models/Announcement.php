<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    public const LEVELS = ['info', 'success', 'warning', 'critical'];
    public const AUDIENCES = ['all', 'guests', 'users', 'authors', 'admins'];

    protected $fillable = [
        'title', 'body', 'level', 'audience', 'cta_label', 'cta_url',
        'starts_at', 'ends_at', 'is_active', 'is_dismissible', 'created_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'is_dismissible' => 'boolean',
    ];

    public function scopeActive(Builder $q): Builder
    {
        $now = now();
        return $q->where('is_active', true)
            ->where(fn ($w) => $w->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($w) => $w->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function targetsUser(?User $user): bool
    {
        return match ($this->audience) {
            'all' => true,
            'guests' => $user === null,
            'users' => $user !== null,
            'authors' => $user !== null && in_array($user->role, ['author', 'admin', 'moderator'], true),
            'admins' => $user !== null && in_array($user->role, ['admin', 'moderator'], true),
            default => true,
        };
    }
}
