<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedSearch extends Model
{
    protected $fillable = ['user_id', 'name', 'filters', 'notify_email', 'last_notified_at'];

    protected $casts = [
        'filters' => 'array',
        'notify_email' => 'boolean',
        'last_notified_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Build a /models URL from the stored filters.
     */
    public function url(): string
    {
        return route('products.index', $this->filters ?: []);
    }

    /**
     * Apply this saved search's filters to a Product query.
     */
    public function apply(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        $f = $this->filters ?? [];

        if (! empty($f['q'])) {
            $term = '%'.$f['q'].'%';
            $query->where(function ($w) use ($term) {
                $w->where('title', 'like', $term)
                  ->orWhere('description', 'like', $term)
                  ->orWhere('slug', 'like', $term);
            });
        }
        if (! empty($f['category'])) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $f['category']));
        }
        if (! empty($f['tag'])) {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $f['tag']));
        }
        if (! empty($f['free'])) {
            $query->where('is_free', true);
        }
        if (! empty($f['license']) && is_array($f['license'])) {
            $query->whereHas('license', fn ($q) => $q->whereIn('slug', $f['license']));
        }
        if (! empty($f['format']) && is_array($f['format'])) {
            $query->whereHas('files', fn ($q) => $q->whereIn('extension', array_map('strtolower', $f['format'])));
        }
        if (isset($f['min_price']) && $f['min_price'] !== null && $f['min_price'] !== '') {
            $query->where('price', '>=', (float) $f['min_price']);
        }
        if (isset($f['max_price']) && $f['max_price'] !== null && $f['max_price'] !== '') {
            $query->where('price', '<=', (float) $f['max_price']);
        }

        return $query;
    }
}
