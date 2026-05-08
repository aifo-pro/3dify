<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::with('createdBy:id,name')->orderByDesc('is_active')->orderByDesc('created_at')->get();
        return view('admin.announcements.index', compact('announcements'));
    }

    public function store(Request $request, AuditLogger $audit)
    {
        $data = $this->validated($request);
        $data['created_by'] = auth()->id();
        $a = Announcement::create($data);
        $audit->record('announcement.create', $a);
        return back()->with('status', __('Оголошення створене.'));
    }

    public function update(Request $request, Announcement $announcement, AuditLogger $audit)
    {
        $announcement->update($this->validated($request));
        $audit->record('announcement.update', $announcement);
        return back()->with('status', __('Оголошення оновлено.'));
    }

    public function destroy(Announcement $announcement, AuditLogger $audit)
    {
        $audit->record('announcement.delete', $announcement);
        $announcement->delete();
        return back()->with('status', __('Видалено.'));
    }

    public function toggle(Announcement $announcement, AuditLogger $audit)
    {
        $announcement->update(['is_active' => ! $announcement->is_active]);
        $audit->record('announcement.toggle', $announcement, ['is_active' => $announcement->is_active]);
        return back();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'body' => ['nullable', 'string', 'max:2000'],
            'level' => ['required', Rule::in(Announcement::LEVELS)],
            'audience' => ['required', Rule::in(Announcement::AUDIENCES)],
            'cta_label' => ['nullable', 'string', 'max:60'],
            'cta_url' => ['nullable', 'url', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable'],
            'is_dismissible' => ['nullable'],
        ]) + [
            'is_active' => $request->boolean('is_active'),
            'is_dismissible' => $request->boolean('is_dismissible', true),
        ];
    }
}
