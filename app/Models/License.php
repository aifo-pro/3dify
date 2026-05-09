<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    use HasLocalizedFields;

    /** Color tokens used by the badge component. Keep names short and Tailwind-safe. */
    public const COLORS = ['emerald', 'sky', 'violet', 'amber', 'rose', 'fuchsia', 'zinc', 'cyan', 'lime'];

    /** Icon slugs shipped with `<x-license-badge>` / `<x-license-icons>`. */
    public const ICONS = [
        'personal',
        'commercial',
        'royalty-free',
        'attribution',
        'no-redistribution',
        'free',
        'premium',
        'non-commercial',
        'remix-allowed',
        'remix-forbidden',
    ];

    protected $fillable = [
        'slug',
        'name',
        'description',
        'badge_label',
        'badge_color',
        'icon_slug',
        'allows_commercial_use',
        'requires_attribution',
        'allows_redistribution',
        'allows_remix',
        'allows_selling_prints',
        'forbids_file_resale',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'allows_commercial_use' => 'boolean',
        'requires_attribution' => 'boolean',
        'allows_redistribution' => 'boolean',
        'allows_remix' => 'boolean',
        'allows_selling_prints' => 'boolean',
        'forbids_file_resale' => 'boolean',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Best-effort fallback to a Tailwind colour even on legacy rows.
     */
    public function badgeColor(): string
    {
        $color = (string) ($this->badge_color ?? '');
        return in_array($color, self::COLORS, true) ? $color : 'emerald';
    }

    /**
     * Pick a sensible icon if none was set explicitly.
     */
    public function iconSlug(): string
    {
        if ($this->icon_slug && in_array($this->icon_slug, self::ICONS, true)) {
            return $this->icon_slug;
        }

        return $this->allows_commercial_use ? 'commercial' : 'personal';
    }

    /**
     * Lightweight short label shown inside compact pill badges.
     */
    public function badgeLabel(): string
    {
        $custom = trim((string) ($this->badge_label ?? ''));
        if ($custom !== '') {
            return $custom;
        }

        return (string) $this->localized('name');
    }

    /**
     * Render-friendly snapshot (used when persisting per-purchase license terms).
     *
     * @return array<string, mixed>
     */
    public function toSnapshot(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'badge_label' => $this->badge_label,
            'badge_color' => $this->badgeColor(),
            'icon_slug' => $this->iconSlug(),
            'allows_commercial_use' => (bool) $this->allows_commercial_use,
            'requires_attribution' => (bool) $this->requires_attribution,
            'allows_redistribution' => (bool) $this->allows_redistribution,
            'allows_remix' => (bool) $this->allows_remix,
            'allows_selling_prints' => (bool) $this->allows_selling_prints,
            'forbids_file_resale' => (bool) $this->forbids_file_resale,
        ];
    }
}
