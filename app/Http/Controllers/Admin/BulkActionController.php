<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BulkActionController extends Controller
{
    public function users(Request $request, AuditLogger $audit)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['suspend', 'unsuspend', 'verify_email', 'unverify_email', 'delete'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $ids = collect($data['ids'])->reject(fn ($id) => (int) $id === auth()->id())->values()->all();
        $count = 0;

        match ($data['action']) {
            'suspend' => User::whereIn('id', $ids)->where('id', '!=', auth()->id())->update(['is_suspended' => true]) and $count = count($ids),
            'unsuspend' => User::whereIn('id', $ids)->update(['is_suspended' => false]) and $count = count($ids),
            'verify_email' => User::whereIn('id', $ids)->whereNull('email_verified_at')->update(['email_verified_at' => now()]) and $count = count($ids),
            'unverify_email' => User::whereIn('id', $ids)->update(['email_verified_at' => null]) and $count = count($ids),
            'delete' => $count = User::whereIn('id', $ids)->where('id', '!=', auth()->id())->where('role', '!=', 'admin')->delete(),
        };

        $audit->record('users.bulk.'.$data['action'], null, ['ids' => $ids, 'count' => $count]);

        return back()->with('status', __(':n записів оброблено.', ['n' => $count]));
    }

    public function products(Request $request, AuditLogger $audit)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['publish', 'reject', 'archive', 'feature', 'unfeature', 'delete'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $ids = $data['ids'];
        $count = 0;

        match ($data['action']) {
            'publish' => $count = Product::whereIn('id', $ids)->update(['status' => 'published', 'published_at' => now()]),
            'reject' => $count = Product::whereIn('id', $ids)->update(['status' => 'rejected']),
            'archive' => $count = Product::whereIn('id', $ids)->update(['status' => 'archived']),
            'feature' => $count = Product::whereIn('id', $ids)->update(['is_featured' => true]),
            'unfeature' => $count = Product::whereIn('id', $ids)->update(['is_featured' => false]),
            'delete' => $count = Product::whereIn('id', $ids)->delete(),
        };

        $audit->record('products.bulk.'.$data['action'], null, ['ids' => $ids, 'count' => $count]);

        return back()->with('status', __(':n записів оброблено.', ['n' => $count]));
    }
}
