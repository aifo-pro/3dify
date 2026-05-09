<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\DatabaseTemplateMail;
use App\Models\Category;
use App\Models\License;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Tag;
use App\Models\User;
use App\Services\AdminStats;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $stats = AdminStats::for($request->query('period'));

        return view('admin.index', [
            'usersCount' => User::count(),
            'productsCount' => Product::count(),
            'pendingCount' => Product::where('status', 'pending')->count(),
            'ordersCount' => Order::count(),
            'paymentsCount' => Payment::count(),
            'stats' => $stats,
        ]);
    }

    public function users(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $role = (string) $request->query('role', '');
        $status = (string) $request->query('status', '');
        $sort = (string) $request->query('sort', 'latest');

        $users = User::query()
            ->withCount(['products', 'orders', 'followers'])
            ->when($q !== '', fn ($qq) => $qq->where(function ($qb) use ($q) {
                $qb->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('username', 'like', "%{$q}%");
            }))
            ->when(in_array($role, ['user', 'author', 'moderator', 'admin'], true), fn ($qq) => $qq->where('role', $role))
            ->when($status === 'active', fn ($qq) => $qq->where('is_suspended', false))
            ->when($status === 'suspended', fn ($qq) => $qq->where('is_suspended', true))
            ->when($status === 'verified', fn ($qq) => $qq->whereNotNull('email_verified_at'))
            ->when($status === 'unverified', fn ($qq) => $qq->whereNull('email_verified_at'))
            ->when($sort === 'name', fn ($qq) => $qq->orderBy('name'))
            ->when($sort === 'oldest', fn ($qq) => $qq->oldest())
            ->when($sort === 'most_products', fn ($qq) => $qq->orderByDesc('products_count'))
            ->when($sort === 'most_orders', fn ($qq) => $qq->orderByDesc('orders_count'))
            ->when(! in_array($sort, ['name', 'oldest', 'most_products', 'most_orders'], true), fn ($qq) => $qq->latest())
            ->paginate(20)
            ->withQueryString();

        $editable = $users->getCollection()->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'username' => $u->username,
            'email' => $u->email,
            'role' => $u->role,
            'locale' => $u->locale ?: 'uk',
            'bio' => (string) $u->bio,
            'is_suspended' => (bool) $u->is_suspended,
            'manual_verification' => (bool) $u->manual_verification,
            'email_verified_at' => optional($u->email_verified_at)->format('d.m.Y H:i'),
            'github_id' => $u->github_id,
            'telegram_id' => $u->telegram_id,
            'telegram_username' => $u->telegram_username,
            'avatar_url' => $u->avatar_path ? Storage::disk('public')->url($u->avatar_path) : null,
            'created_at' => optional($u->created_at)->format('d.m.Y'),
            'products_count' => $u->products_count ?? 0,
            'orders_count' => $u->orders_count ?? 0,
            'followers_count' => $u->followers_count ?? 0,
            'profile_url' => $u->profileUrl(),
            'is_self' => $u->id === auth()->id(),
        ])->all();

        return view('admin.users', [
            'users' => $users,
            'editable' => $editable,
            'q' => $q,
            'role' => $role,
            'status' => $status,
            'sort' => $sort,
            'totalCount' => User::count(),
            'roleCounts' => User::query()->selectRaw('role, COUNT(*) as c')->groupBy('role')->pluck('c', 'role')->all(),
            'suspendedCount' => User::where('is_suspended', true)->count(),
            'verifiedCount' => User::whereNotNull('email_verified_at')->count(),
            'newThisWeek' => User::where('created_at', '>=', now()->subDays(7))->count(),
        ]);
    }

    public function updateUser(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['nullable', 'string', 'max:60', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['user', 'author', 'moderator', 'admin'])],
            'locale' => ['nullable', Rule::in(['uk', 'en'])],
            'bio' => ['nullable', 'string', 'max:1000'],
            'is_suspended' => ['nullable', 'boolean'],
        ]);

        // Safety: cannot demote / suspend yourself.
        if ($user->id === auth()->id()) {
            if ($data['role'] !== $user->role) {
                return back()->withErrors(['role' => 'Не можна змінити власну роль.']);
            }
            if ($request->boolean('is_suspended')) {
                return back()->withErrors(['is_suspended' => 'Не можна заблокувати власний акаунт.']);
            }
        }

        // Safety: cannot demote the last admin.
        if ($user->role === 'admin' && $data['role'] !== 'admin') {
            $remainingAdmins = User::where('role', 'admin')->where('id', '!=', $user->id)->count();
            if ($remainingAdmins === 0) {
                return back()->withErrors(['role' => 'Не можна понизити останнього адміністратора.']);
            }
        }

        $user->update([
            'name' => $data['name'],
            'username' => $data['username'] ?: null,
            'email' => $data['email'],
            'role' => $data['role'],
            'locale' => $data['locale'] ?: 'uk',
            'bio' => $data['bio'] ?? null,
            'is_suspended' => $request->boolean('is_suspended'),
        ]);

        return back()->with('status', 'Користувача оновлено.');
    }

    public function resetUserPassword(Request $request, User $user)
    {
        $data = $request->validate([
            'password' => ['nullable', 'confirmed', Password::min(6)],
        ]);

        $newPassword = $data['password'] ?? Str::random(12);
        $user->update(['password' => Hash::make($newPassword)]);

        $message = 'Пароль скинуто.';
        if (! ($data['password'] ?? null)) {
            $message .= ' Новий пароль: '.$newPassword;
        }

        return back()->with('status', $message);
    }

    public function toggleUserVerification(User $user)
    {
        $user->update([
            'email_verified_at' => $user->email_verified_at ? null : now(),
        ]);

        return back()->with('status', $user->email_verified_at
            ? 'Email підтверджено.'
            : 'Email знято з підтвердження.');
    }

    public function toggleManualVerification(User $user, AuditLogger $audit)
    {
        $user->update(['manual_verification' => ! $user->manual_verification]);
        $audit->record('user.manual_verification.toggle', $user, ['manual_verification' => $user->manual_verification]);

        return back()->with('status', $user->manual_verification
            ? __('Автору присвоєно ручний знак Verified.')
            : __('Знак Verified знято.'));
    }

    public function destroyUser(User $user, AuditLogger $audit)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['delete' => 'Не можна видалити власний акаунт.']);
        }

        if ($user->role === 'admin') {
            $remainingAdmins = User::where('role', 'admin')->where('id', '!=', $user->id)->count();
            if ($remainingAdmins === 0) {
                return back()->withErrors(['delete' => 'Не можна видалити останнього адміністратора.']);
            }
        }

        $audit->record('user.delete', $user, ['email' => $user->email, 'role' => $user->role]);
        $user->delete();

        return redirect()->route('admin.users')->with('status', 'Користувача видалено.');
    }

    public function products()
    {
        return view('admin.products', ['products' => Product::with(['author', 'category'])->latest()->paginate(20)]);
    }

    public function moderate(Request $request, Product $product, AuditLogger $audit)
    {
        $data = $request->validate(['status' => ['required', 'in:published,rejected,pending,archived'], 'moderation_note' => ['nullable', 'string', 'max:500']]);
        $previousStatus = $product->status;

        $product->update([
            'status' => $data['status'],
            'moderation_note' => $data['moderation_note'] ?? null,
            'published_at' => $data['status'] === 'published' ? now() : $product->published_at,
        ]);

        $audit->record('product.moderate', $product, ['status' => $data['status']]);

        $author = $product->author;
        if ($author && $author->email) {
            $locale = $author->locale ?: 'uk';
            $productTitle = $product->localized('title', $locale);

            $payload = [
                'user' => ['name' => $author->displayName()],
                'product' => [
                    'title' => $productTitle,
                    'url' => route('products.show', $product),
                ],
            ];

            if ($data['status'] === 'published' && $previousStatus !== 'published') {
                Mail::to($author)->queue(new DatabaseTemplateMail('model_approved', $author, $payload));
            }

            if ($data['status'] === 'rejected' && $previousStatus !== 'rejected') {
                $payload['moderation'] = ['note' => (string) ($product->moderation_note ?? '')];
                Mail::to($author)->queue(new DatabaseTemplateMail('model_rejected', $author, $payload));
            }
        }

        return back()->with('status', 'Статус моделі оновлено.');
    }

    public function orders()
    {
        return view('admin.orders', ['orders' => Order::with(['user', 'items.product'])->latest()->paginate(20)]);
    }

    public function payments()
    {
        return view('admin.payments', ['payments' => Payment::with('order.user')->latest()->paginate(20)]);
    }

    // ---------------------------------------------------------------------
    // Categories
    // ---------------------------------------------------------------------

    public function categories(Request $request)
    {
        $q = trim((string) $request->query('q'));

        $categories = Category::query()
            ->withCount('products')
            ->with('parent:id,name,slug')
            ->when($q !== '', fn ($qq) => $qq->where('slug', 'like', "%{$q}%"))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(30)
            ->withQueryString();

        $editable = Category::orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name', 'slug', 'description', 'parent_id', 'sort_order', 'is_active'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'slug' => $c->slug,
                'parent_id' => $c->parent_id,
                'sort_order' => $c->sort_order,
                'is_active' => (bool) $c->is_active,
                'name_uk' => is_array($c->name) ? ($c->name['uk'] ?? '') : (string) $c->name,
                'name_en' => is_array($c->name) ? ($c->name['en'] ?? '') : '',
                'description_uk' => is_array($c->description) ? ($c->description['uk'] ?? '') : (string) $c->description,
                'display_name' => $c->localized('name'),
            ]);

        return view('admin.categories', [
            'categories' => $categories,
            'allCategories' => Category::orderBy('sort_order')->orderBy('id')->get(['id', 'name', 'slug', 'parent_id']),
            'editable' => $editable,
            'q' => $q,
            'totalCount' => Category::count(),
            'productsCount' => Product::count(),
        ]);
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'slug' => ['required', 'alpha_dash', 'unique:categories,slug'],
            'name_uk' => ['required', 'string', 'max:120'],
            'name_en' => ['nullable', 'string', 'max:120'],
            'description_uk' => ['nullable', 'string', 'max:500'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Category::create([
            'slug' => $data['slug'],
            'name' => ['uk' => $data['name_uk'], 'en' => $data['name_en'] ?: $data['name_uk']],
            'description' => ['uk' => $data['description_uk'] ?? '', 'en' => $data['description_uk'] ?? ''],
            'parent_id' => $data['parent_id'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('status', 'Категорію створено.');
    }

    public function updateCategory(Request $request, Category $category)
    {
        $data = $request->validate([
            'slug' => ['required', 'alpha_dash', Rule::unique('categories', 'slug')->ignore($category->id)],
            'name_uk' => ['required', 'string', 'max:120'],
            'name_en' => ['nullable', 'string', 'max:120'],
            'description_uk' => ['nullable', 'string', 'max:500'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id', Rule::notIn([$category->id])],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category->update([
            'slug' => $data['slug'],
            'name' => ['uk' => $data['name_uk'], 'en' => $data['name_en'] ?: $data['name_uk']],
            'description' => ['uk' => $data['description_uk'] ?? '', 'en' => $data['description_uk'] ?? ''],
            'parent_id' => $data['parent_id'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', 'Категорію оновлено.');
    }

    public function destroyCategory(Category $category)
    {
        if ($category->products()->exists()) {
            return back()->withErrors(['delete' => 'Неможливо видалити: до категорії привʼязані моделі.']);
        }

        $category->delete();

        return back()->with('status', 'Категорію видалено.');
    }

    // ---------------------------------------------------------------------
    // Tags
    // ---------------------------------------------------------------------

    public function tags(Request $request)
    {
        $q = trim((string) $request->query('q'));

        $tags = Tag::query()
            ->withCount('products')
            ->when($q !== '', fn ($qq) => $qq->where('slug', 'like', "%{$q}%"))
            ->orderBy('slug')
            ->paginate(40)
            ->withQueryString();

        return view('admin.tags', [
            'tags' => $tags,
            'q' => $q,
            'totalCount' => Tag::count(),
            'usedCount' => Tag::has('products')->count(),
        ]);
    }

    public function storeTag(Request $request)
    {
        $data = $request->validate([
            'slug' => ['required', 'alpha_dash', 'unique:tags,slug'],
            'name_uk' => ['required', 'string', 'max:80'],
            'name_en' => ['nullable', 'string', 'max:80'],
        ]);

        Tag::create([
            'slug' => $data['slug'],
            'name' => ['uk' => $data['name_uk'], 'en' => $data['name_en'] ?: $data['name_uk']],
        ]);

        return back()->with('status', 'Тег створено.');
    }

    public function updateTag(Request $request, Tag $tag)
    {
        $data = $request->validate([
            'slug' => ['required', 'alpha_dash', Rule::unique('tags', 'slug')->ignore($tag->id)],
            'name_uk' => ['required', 'string', 'max:80'],
            'name_en' => ['nullable', 'string', 'max:80'],
        ]);

        $tag->update([
            'slug' => $data['slug'],
            'name' => ['uk' => $data['name_uk'], 'en' => $data['name_en'] ?: $data['name_uk']],
        ]);

        return back()->with('status', 'Тег оновлено.');
    }

    public function destroyTag(Tag $tag)
    {
        $tag->products()->detach();
        $tag->delete();

        return back()->with('status', 'Тег видалено.');
    }

    // ---------------------------------------------------------------------
    // Licenses
    // ---------------------------------------------------------------------

    public function licenses(Request $request)
    {
        $q = trim((string) $request->query('q'));

        $licenses = License::query()
            ->withCount('products')
            ->when($q !== '', fn ($qq) => $qq->where('slug', 'like', "%{$q}%"))
            ->orderBy('slug')
            ->paginate(20)
            ->withQueryString();

        return view('admin.licenses', [
            'licenses' => $licenses,
            'q' => $q,
            'totalCount' => License::count(),
            'commercialCount' => License::where('allows_commercial_use', true)->count(),
        ]);
    }

    public function storeLicense(Request $request)
    {
        $data = $this->validatedLicense($request, null);

        License::create($this->licensePayload($data, $request));

        return back()->with('status', 'Ліцензію створено.');
    }

    public function updateLicense(Request $request, License $license)
    {
        $data = $this->validatedLicense($request, $license);

        $license->update($this->licensePayload($data, $request));

        return back()->with('status', 'Ліцензію оновлено.');
    }

    private function validatedLicense(Request $request, ?License $existing): array
    {
        return $request->validate([
            'slug' => ['required', 'alpha_dash', $existing
                ? Rule::unique('licenses', 'slug')->ignore($existing->id)
                : 'unique:licenses,slug'],
            'name_uk' => ['required', 'string', 'max:120'],
            'name_en' => ['nullable', 'string', 'max:120'],
            'description_uk' => ['nullable', 'string', 'max:1000'],
            'description_en' => ['nullable', 'string', 'max:1000'],
            'badge_label' => ['nullable', 'string', 'max:60'],
            'badge_color' => ['nullable', Rule::in(License::COLORS)],
            'icon_slug' => ['nullable', Rule::in(License::ICONS)],
            'allows_commercial_use' => ['nullable', 'boolean'],
            'requires_attribution' => ['nullable', 'boolean'],
            'allows_redistribution' => ['nullable', 'boolean'],
            'allows_remix' => ['nullable', 'boolean'],
            'allows_selling_prints' => ['nullable', 'boolean'],
            'forbids_file_resale' => ['nullable', 'boolean'],
        ]);
    }

    private function licensePayload(array $data, Request $request): array
    {
        return [
            'slug' => $data['slug'],
            'name' => ['uk' => $data['name_uk'], 'en' => $data['name_en'] ?: $data['name_uk']],
            'description' => ['uk' => $data['description_uk'] ?? '', 'en' => $data['description_en'] ?? $data['description_uk'] ?? ''],
            'badge_label' => $data['badge_label'] ?? null,
            'badge_color' => $data['badge_color'] ?? null,
            'icon_slug' => $data['icon_slug'] ?? null,
            'allows_commercial_use' => $request->boolean('allows_commercial_use'),
            'requires_attribution' => $request->boolean('requires_attribution'),
            'allows_redistribution' => $request->boolean('allows_redistribution'),
            'allows_remix' => $request->boolean('allows_remix', true),
            'allows_selling_prints' => $request->boolean('allows_selling_prints'),
            'forbids_file_resale' => $request->boolean('forbids_file_resale', true),
        ];
    }

    public function destroyLicense(License $license)
    {
        if (Product::where('license_id', $license->id)->exists()) {
            return back()->withErrors(['delete' => 'Неможливо видалити: до ліцензії привʼязані моделі.']);
        }

        $license->delete();

        return back()->with('status', 'Ліцензію видалено.');
    }
}
