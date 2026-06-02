<?php

namespace App\Models;

use App\Mail\RenderedTemplateMail;
use App\Services\EmailTemplateRenderer;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Testing\Fakes\NotificationFake;
use App\Notifications\TemplatedPasswordResetNotification;

#[Fillable([
    'name', 'display_name', 'username', 'email', 'email_verified_at', 'password', 'role',
    'bio', 'bio_uk', 'bio_en', 'avatar_path', 'cover_path', 'website_url', 'telegram_url',
    'instagram_url', 'youtube_url', 'github_url', 'twitter_url', 'location', 'country_code', 'city',
    'contact_enabled', 'github_id', 'telegram_id', 'telegram_username', 'locale',
    'is_suspended', 'manual_verification', 'referral_code', 'referred_by',
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

    public function customOrders()
    {
        return $this->hasMany(CustomOrder::class, 'buyer_id');
    }

    public function authoredCustomOrders()
    {
        return $this->hasMany(CustomOrder::class, 'author_id');
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

    public function accountBalanceTransactions()
    {
        return $this->hasMany(AccountBalanceTransaction::class);
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

        if (str_starts_with($this->avatar_path, 'http://') || str_starts_with($this->avatar_path, 'https://')) {
            return $this->avatar_path;
        }

        return Storage::disk('public')->url($this->avatar_path);
    }

    public function coverUrl(): ?string
    {
        if (! $this->cover_path) {
            return null;
        }

        return Storage::disk('public')->url($this->cover_path);
    }

    public function countryMeta(): ?array
    {
        if (! $this->country_code) {
            return null;
        }

        return config('countries.'.strtoupper($this->country_code));
    }

    public function countryName(?string $locale = null): ?string
    {
        $country = $this->countryMeta();
        if (! $country) {
            return null;
        }

        $locale ??= app()->getLocale();

        return $country[$locale] ?? $country['en'] ?? null;
    }

    public function countryFlag(): ?string
    {
        if (! $this->country_code || ! preg_match('/^[A-Z]{2}$/', strtoupper($this->country_code))) {
            return null;
        }

        return collect(str_split(strtoupper($this->country_code)))
            ->map(fn (string $letter) => mb_chr(127397 + ord($letter), 'UTF-8'))
            ->implode('');
    }

    public function publicLocation(): ?string
    {
        $parts = array_filter([
            $this->countryName(),
            $this->city,
        ]);

        return $parts ? implode(', ', $parts) : $this->location;
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

    /**
     * Send password reset mail from DB templates (not Laravel's default notification HTML).
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        if (app()->environment('testing') && Notification::getFacadeRoot() instanceof NotificationFake) {
            $this->notify(new TemplatedPasswordResetNotification($token));

            return;
        }

        $url = url(route('password.reset', [
            'token' => $token,
            'email' => $this->getEmailForPasswordReset(),
        ], false));
        $expire = (string) config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        $rendered = app(EmailTemplateRenderer::class)->render('password_reset', [
            'user' => [
                'name' => $this->name,
                'email' => $this->email,
                'username' => $this->username,
                'display_name' => $this->displayName(),
                'locale' => $this->locale,
            ],
            'link' => $url,
            'reset' => [
                'url' => $url,
                'expires_minutes' => $expire,
            ],
        ], $this->locale ?: app()->getLocale());

        Mail::to($this->getEmailForPasswordReset())->queue(
            new RenderedTemplateMail($rendered['subject'], $rendered['body'])
        );
    }

    /**
     * Send verification mail from DB templates.
     */
    public function sendEmailVerificationNotification(): void
    {
        $expire = (int) Config::get('auth.verification.expire', 60);
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes($expire),
            [
                'id' => $this->getKey(),
                'hash' => sha1($this->getEmailForVerification()),
            ]
        );

        $rendered = app(EmailTemplateRenderer::class)->render('email_verification', [
            'user' => [
                'name' => $this->name,
                'email' => $this->email,
                'username' => $this->username,
                'display_name' => $this->displayName(),
                'locale' => $this->locale,
            ],
            'link' => $verificationUrl,
            'verification' => [
                'url' => $verificationUrl,
                'expires_minutes' => (string) $expire,
            ],
        ], $this->locale ?: app()->getLocale());

        Mail::to($this->getEmailForVerification())->queue(
            new RenderedTemplateMail($rendered['subject'], $rendered['body'])
        );
    }
}
