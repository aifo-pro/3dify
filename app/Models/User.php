<?php

namespace App\Models;

use App\Models\Product;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name', 'display_name', 'username', 'email', 'email_verified_at', 'password', 'role',
    'bio', 'bio_uk', 'bio_en', 'avatar_path', 'cover_path', 'website_url', 'telegram_url',
    'instagram_url', 'youtube_url', 'github_url', 'twitter_url', 'location',
    'contact_enabled', 'github_id', 'telegram_id', 'telegram_username', 'locale',
    'is_suspended', 'manual_verification',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_suspended' => 'boolean',
            'manual_verification' => 'boolean',
            'contact_enabled' => 'boolean',
        ];
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Users that follow this user (this user is the author).
     */
    public function followers()
    {
        return $this->belongsToMany(self::class, 'author_followers', 'author_id', 'follower_id')
            ->withTimestamps();
    }

    /**
     * Authors this user follows.
     */
    public function following()
    {
        return $this->belongsToMany(self::class, 'author_followers', 'follower_id', 'author_id')
            ->withTimestamps();
    }

    public function followersCount(): int
    {
        return $this->followers()->count();
    }

    public function isFollowedBy(?self $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->followers()->where('follower_id', $user->id)->exists();
    }

    public function wishlist()
    {
        return $this->belongsToMany(Product::class, 'wishlists')->withTimestamps();
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function payouts()
    {
        return $this->hasMany(Payout::class, 'author_id');
    }

    public function printers()
    {
        return $this->hasMany(PrinterProfile::class);
    }

    public function defaultPrinter(): ?PrinterProfile
    {
        return $this->printers()->where('is_default', true)->first()
            ?? $this->printers()->first();
    }

    public function savedSearches()
    {
        return $this->hasMany(SavedSearch::class);
    }

    public function profileUrl(): string
    {
        return route('authors.show', ['user' => $this->username ?: $this->id]);
    }

    public function displayName(): string
    {
        return $this->display_name ?: $this->name;
    }

    public function localizedBio(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return match ($locale) {
            'en' => $this->bio_en ?: $this->bio_uk ?: $this->bio,
            default => $this->bio_uk ?: $this->bio ?: $this->bio_en,
        };
    }

    public function avatarUrl(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->avatar_path);
    }

    public function coverUrl(): ?string
    {
        if (! $this->cover_path) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->cover_path);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasRole(string|array $roles): bool
    {
        return in_array($this->role, (array) $roles, true);
    }

    public function canModerate(): bool
    {
        return $this->hasRole(['admin', 'moderator']);
    }

    /**
     * Author verification tier — automatic, no manual approval required.
     *  - "verified": 5+ published products, account 30+ days old, average rating >= 4.0
     *  - "trusted": 1+ paid sale and 90+ days old
     *  - null otherwise
     */
    public function verificationTier(): ?string
    {
        if ($this->manual_verification) {
            return 'verified';
        }

        $createdAt = $this->created_at;
        $ageDays = $createdAt ? $createdAt->diffInDays(now()) : 0;
        if ($ageDays < 30) {
            return null;
        }

        $publishedCount = $this->products()->where('status', 'published')->count();

        if ($publishedCount >= 5) {
            $avg = (float) ProductReview::query()
                ->whereIn('product_id', $this->products()->pluck('id'))
                ->avg('rating');
            if ($avg === 0.0 || $avg >= 4.0) {
                return 'verified';
            }
        }

        if ($ageDays >= 90) {
            $hasSale = OrderItem::query()
                ->where('author_id', $this->id)
                ->whereHas('order', fn ($q) => $q->where('status', 'paid'))
                ->exists();
            if ($hasSale) {
                return 'trusted';
            }
        }

        return null;
    }

    public function isVerifiedAuthor(): bool
    {
        return $this->verificationTier() === 'verified';
    }
}
