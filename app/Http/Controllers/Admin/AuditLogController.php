<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::query()->with('user')->latest();

        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }
        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }
        if ($q = trim((string) $request->input('q', ''))) {
            $query->where(function ($w) use ($q) {
                $w->where('action', 'like', '%'.$q.'%')
                  ->orWhere('subject_type', 'like', '%'.$q.'%')
                  ->orWhere('ip_address', 'like', '%'.$q.'%');
            });
        }

        $logs = $query->paginate(50)->withQueryString();

        $actions = AuditLog::query()
            ->select('action')
            ->groupBy('action')
            ->orderBy('action')
            ->pluck('action');

        return view('admin.audit', compact('logs', 'actions'));
    }
}
