<?php

namespace App\Console\Commands;

use App\Models\KycVerification;
use App\Services\DiditKycService;
use Illuminate\Console\Command;

class SyncPendingKyc extends Command
{
    protected $signature = 'kyc:sync-pending {--limit=100 : Max verifications to sync per run}';

    protected $description = 'Poll Didit for pending KYC verifications and sync their status (webhook fallback).';

    public function handle(DiditKycService $kyc): int
    {
        $limit = (int) $this->option('limit');
        $synced = 0;

        KycVerification::query()
            ->where('status', KycVerification::STATUS_PENDING)
            ->whereNotNull('provider_session_id')
            // Only sessions created in the last 7 days — older ones are stale.
            ->where('created_at', '>=', now()->subDays(7))
            ->with('user')
            ->orderBy('updated_at')
            ->limit($limit)
            ->get()
            ->each(function (KycVerification $verification) use ($kyc, &$synced) {
                try {
                    if ($kyc->fetchAndSyncSession($verification)) {
                        $synced++;
                        $this->info("Synced #{$verification->id} → {$verification->fresh()->status}");
                    }
                } catch (\Throwable $exception) {
                    $this->warn("Failed #{$verification->id}: {$exception->getMessage()}");
                }
            });

        $this->info("Done. {$synced} verification(s) updated.");

        return self::SUCCESS;
    }
}
