<?php

namespace App\Http\Controllers;

use App\Models\LegalPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PageController extends Controller
{
    /**
     * Display a CMS-managed legal/info page.
     */
    public function show(Request $request, string $slug)
    {
        abort_unless(Schema::hasTable('legal_pages'), 404);

        $page = LegalPage::lookup($slug);

        abort_unless($page && $page->is_published, 404);

        $available = LegalPage::query()
            ->published()
            ->where('locale', app()->getLocale())
            ->orderBy('sort_order')
            ->get(['slug', 'title']);

        if ($available->isEmpty()) {
            $available = LegalPage::query()
                ->published()
                ->orderBy('sort_order')
                ->get(['slug', 'title']);
        }

        return view('marketplace.pages.show', [
            'page' => $page,
            'available' => $available,
            'seoTitle' => $page->meta_title ?: $page->title,
            'seoDescription' => $page->meta_description,
        ]);
    }
}
