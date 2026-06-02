<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KycVerification;
use Illuminate\Http\Request;

class KycAdminController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        $search = trim((string) $request->query('q', ''));

        $verifications = KycVerification::query()
            ->with('user')
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('user', fn ($user) => $user
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%"))
                    ->orWhere('provider_session_id', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = collect(KycVerification::STATUSES)
            ->mapWithKeys(fn ($item) => [$item => KycVerification::where('status', $item)->count()])
            ->all();
        $counts['all'] = array_sum($counts);

        return view('admin.kyc.index', [
            'verifications' => $verifications,
            'status' => $status,
            'search' => $search,
            'counts' => $counts,
        ]);
    }
}
