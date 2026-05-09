<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LegalPage;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LegalPagesController extends Controller
{
    /**
     * Show editor for an existing page.
     */
    public function edit(LegalPage $page)
    {
        return view('admin.pages.form', [
            'page' => $page,
            'mode' => 'edit',
            'defaultSlugs' => LegalPage::defaultSlugs(),
        ]);
    }

    /**
     * Show editor for a new page.
     */
    public function create(Request $request)
    {
        $page = new LegalPage([
            'slug' => (string) $request->query('slug', ''),
            'locale' => (string) $request->query('locale', app()->getLocale()),
            'is_published' => true,
            'sort_order' => 0,
        ]);

        return view('admin.pages.form', [
            'page' => $page,
            'mode' => 'create',
            'defaultSlugs' => LegalPage::defaultSlugs(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit)
    {
        $data = $this->validated($request, null);
        $data['updated_by_id'] = auth()->id();

        $page = LegalPage::create($data);
        $audit->record('legal_page.create', $page);

        return redirect()
            ->route('admin.pages.edit', $page)
            ->with('status', __('Сторінку створено.'));
    }

    public function update(Request $request, LegalPage $page, AuditLogger $audit)
    {
        $data = $this->validated($request, $page);
        $data['updated_by_id'] = auth()->id();

        $page->update($data);
        $audit->record('legal_page.update', $page);

        return redirect()
            ->route('admin.pages.edit', $page)
            ->with('status', __('Сторінку оновлено.'));
    }

    public function destroy(LegalPage $page, AuditLogger $audit)
    {
        $audit->record('legal_page.delete', $page);
        $page->delete();

        return redirect()
            ->to(route('admin.content', ['tab' => 'pages']))
            ->with('status', __('Сторінку видалено.'));
    }

    public function toggle(LegalPage $page, AuditLogger $audit)
    {
        $page->update(['is_published' => ! $page->is_published]);
        $audit->record('legal_page.toggle', $page, ['is_published' => $page->is_published]);

        return back()->with('status', $page->is_published ? __('Сторінку опубліковано.') : __('Сторінку приховано.'));
    }

    private function validated(Request $request, ?LegalPage $existing): array
    {
        return $request->validate([
            'slug' => [
                'required', 'string', 'max:64',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('legal_pages', 'slug')
                    ->where(fn ($q) => $q->where('locale', $request->input('locale')))
                    ->ignore($existing?->id),
            ],
            'locale' => ['required', 'string', 'max:8'],
            'title' => ['required', 'string', 'max:200'],
            'subtitle' => ['nullable', 'string', 'max:300'],
            'body' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'is_published' => ['nullable'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]) + [
            'is_published' => $request->boolean('is_published'),
            'sort_order' => (int) $request->input('sort_order', 0),
        ];
    }
}
