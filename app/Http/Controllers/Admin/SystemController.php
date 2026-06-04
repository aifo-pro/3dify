<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class SystemController extends Controller
{
    public function index()
    {
        $stats = [
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'env' => app()->environment(),
            'debug' => config('app.debug') ? 'on' : 'off',
            'maintenance' => app()->isDownForMaintenance(),
            'cache' => config('cache.default'),
            'queue' => config('queue.default'),
            'mail' => config('mail.default'),
            'driver' => config('database.default'),
            'storage_used' => $this->dirSize(storage_path('app')),
            'logs_size' => $this->dirSize(storage_path('logs')),
        ];

        $failedJobsCount = 0;
        if (Schema::hasTable('failed_jobs')) {
            $failedJobsCount = DB::table('failed_jobs')->count();
        }

        $jobsCount = Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0;

        $diskUsage = [];
        foreach (['public', 'local'] as $disk) {
            try {
                $diskUsage[$disk] = $this->dirSize(storage_path('app/'.($disk === 'public' ? 'public' : '')));
            } catch (\Throwable) {
                $diskUsage[$disk] = 0;
            }
        }

        $checks = [
            'app_key' => [
                'label' => 'APP_KEY',
                'ok' => (string) config('app.key') !== '',
                'detail' => (string) config('app.key') !== '' ? __('Налаштовано') : __('Порожній ключ застосунку'),
            ],
            'storage_writable' => [
                'label' => 'storage writable',
                'ok' => is_writable(storage_path()),
                'detail' => storage_path(),
            ],
            'cache_writable' => [
                'label' => 'bootstrap/cache writable',
                'ok' => is_writable(base_path('bootstrap/cache')),
                'detail' => base_path('bootstrap/cache'),
            ],
            'public_storage' => [
                'label' => 'public/storage',
                'ok' => is_link(public_path('storage')) || is_dir(public_path('storage')),
                'detail' => __('Потрібно для локальних фото та файлів'),
            ],
            'mail_from' => [
                'label' => 'MAIL_FROM',
                'ok' => (string) config('mail.from.address') !== '',
                'detail' => (string) config('mail.from.address'),
            ],
            'smtp' => [
                'label' => 'SMTP',
                'ok' => config('mail.default') !== 'smtp' || (
                    (string) config('mail.mailers.smtp.host') !== ''
                    && (string) config('mail.mailers.smtp.username') !== ''
                    && (string) config('mail.mailers.smtp.password') !== ''
                    && (string) config('mail.from.address') !== ''
                ),
                'detail' => config('mail.default') === 'smtp'
                    ? sprintf(
                        '%s:%s %s · username %s · password %s',
                        config('mail.mailers.smtp.host') ?: 'no-host',
                        config('mail.mailers.smtp.port') ?: 'no-port',
                        config('mail.mailers.smtp.scheme') ?: 'no-encryption',
                        config('mail.mailers.smtp.username') ? 'set' : 'missing',
                        config('mail.mailers.smtp.password') ? 'set' : 'missing'
                    )
                    : __('SMTP не активний: поточний mailer :mailer', ['mailer' => config('mail.default')]),
            ],
            'aifo_secret' => [
                'label' => 'AIFO secret',
                'ok' => \App\Services\AifoPaymentService::webhookSigningSecret() !== '',
                'detail' => \App\Services\AifoPaymentService::webhookSigningSecret() !== '' ? __('Є ключ webhook/HMAC') : __('Ключ не задано'),
            ],
        ];

        return view('admin.system.index', compact('stats', 'failedJobsCount', 'jobsCount', 'diskUsage', 'checks'));
    }

    public function toggleMaintenance(Request $request, AuditLogger $audit)
    {
        if (app()->isDownForMaintenance()) {
            Artisan::call('up');
            $audit->record('system.up');
        } else {
            Artisan::call('down', ['--render' => 'errors::503']);
            $audit->record('system.down');
        }
        return back()->with('status', __('Режим обслуговування перемкнено.'));
    }

    public function clearCache(Request $request, AuditLogger $audit)
    {
        $type = $request->input('type', 'all');
        $commands = match ($type) {
            'config' => ['config:clear'],
            'route' => ['route:clear'],
            'view' => ['view:clear'],
            'cache' => ['cache:clear'],
            'opcache' => ['optimize:clear'],
            default => ['cache:clear', 'config:clear', 'route:clear', 'view:clear', 'optimize:clear'],
        };
        foreach ($commands as $cmd) {
            try {
                Artisan::call($cmd);
            } catch (\Throwable $e) {
                // ignore
            }
        }
        $audit->record('system.cache.clear', null, ['type' => $type]);
        return back()->with('status', __('Кеш очищено.'));
    }

    public function failedJobs()
    {
        if (! Schema::hasTable('failed_jobs')) {
            return view('admin.system.failed-jobs', ['jobs' => collect(), 'unavailable' => true]);
        }

        $jobs = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(200)
            ->get()
            ->map(function ($j) {
                $payload = json_decode($j->payload, true);
                return (object) [
                    'id' => $j->id,
                    'uuid' => $j->uuid ?? null,
                    'connection' => $j->connection,
                    'queue' => $j->queue,
                    'exception_first' => mb_strimwidth(strtok($j->exception, "\n"), 0, 200, '…'),
                    'exception' => $j->exception,
                    'failed_at' => $j->failed_at,
                    'job_name' => $payload['displayName'] ?? ($payload['job'] ?? null),
                ];
            });

        return view('admin.system.failed-jobs', ['jobs' => $jobs, 'unavailable' => false]);
    }

    public function retryJob(Request $request, AuditLogger $audit, $id = 'all')
    {
        if ($id === 'all') {
            Artisan::call('queue:retry', ['id' => ['all']]);
            $audit->record('system.queue.retry-all');
        } else {
            Artisan::call('queue:retry', ['id' => [$id]]);
            $audit->record('system.queue.retry', null, ['id' => $id]);
        }
        return back()->with('status', __('Завдання у черзі.'));
    }

    public function deleteJob(Request $request, AuditLogger $audit, $id)
    {
        Artisan::call('queue:forget', ['id' => $id]);
        $audit->record('system.queue.forget', null, ['id' => $id]);
        return back()->with('status', __('Видалено.'));
    }

    public function flushFailed(AuditLogger $audit)
    {
        Artisan::call('queue:flush');
        $audit->record('system.queue.flush');
        return back()->with('status', __('Усі провалені завдання видалені.'));
    }

    public function logs(Request $request)
    {
        $logFiles = [];
        if (is_dir(storage_path('logs'))) {
            foreach (glob(storage_path('logs/*.log')) as $file) {
                $logFiles[] = ['name' => basename($file), 'size' => filesize($file), 'modified' => filemtime($file)];
            }
        }
        usort($logFiles, fn ($a, $b) => $b['modified'] <=> $a['modified']);

        $selected = $request->input('file', 'laravel.log');
        $tail = '';
        $lines = (int) $request->input('lines', 200);
        $lines = max(50, min(2000, $lines));

        $path = storage_path('logs/'.basename($selected));
        if (is_file($path) && is_readable($path)) {
            $size = filesize($path);
            $maxSize = 5 * 1024 * 1024; // 5MB cap
            if ($size > $maxSize) {
                $fp = fopen($path, 'r');
                fseek($fp, -$maxSize, SEEK_END);
                $tail = fread($fp, $maxSize);
                fclose($fp);
            } else {
                $tail = file_get_contents($path);
            }
            $arr = preg_split("/\n/", $tail);
            $arr = array_slice($arr, -$lines);
            $tail = implode("\n", $arr);
        }

        return view('admin.system.logs', compact('logFiles', 'selected', 'tail', 'lines'));
    }

    private function dirSize(string $dir): int
    {
        if (! is_dir($dir)) return 0;
        $size = 0;
        try {
            $iter = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
            foreach ($iter as $file) {
                if ($file->isFile()) $size += $file->getSize();
            }
        } catch (\Throwable) {}
        return $size;
    }
}
