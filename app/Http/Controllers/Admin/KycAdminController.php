<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KycVerification;
use App\Services\AuditLogger;
use App\Services\DiditKycService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

    /**
     * Re-pull the decision from Didit for one verification (manual sync).
     */
    public function sync(KycVerification $verification, DiditKycService $kyc)
    {
        try {
            $kyc->fetchAndSyncSession($verification);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['kyc' => __('kyc.errors.sync_failed')]);
        }

        return back()->with('status', __('kyc.admin.synced'));
    }

    /**
     * Manual override: admin approves or rejects a verification directly.
     */
    public function updateStatus(Request $request, KycVerification $verification, DiditKycService $kyc, AuditLogger $audit)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([
                KycVerification::STATUS_APPROVED,
                KycVerification::STATUS_REJECTED,
                KycVerification::STATUS_PENDING,
            ])],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $updates = ['status' => $data['status'], 'decision' => 'manual_'.$data['status']];

        if ($data['status'] === KycVerification::STATUS_APPROVED) {
            $updates['approved_at'] = $verification->approved_at ?: now();
            $updates['rejection_reason'] = null;
        } elseif ($data['status'] === KycVerification::STATUS_REJECTED) {
            $updates['rejected_at'] = $verification->rejected_at ?: now();
            $updates['rejection_reason'] = $data['reason'] ?? null;
        }

        $verification->update($updates);
        $kyc->syncUserStatus($verification->user, $verification);

        $audit->record('kyc.admin.manual_status', $verification, [
            'status' => $data['status'],
            'admin_id' => $request->user()->id,
        ]);

        return back()->with('status', __('kyc.admin.status_updated'));
    }
}
