<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\SavedSearch;
use Illuminate\Http\Request;

class SavedSearchController extends Controller
{
    public function index(Request $request)
    {
        return view('marketplace.saved-searches.index', [
            'searches' => $request->user()->savedSearches()->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'filters' => ['nullable', 'array'],
            'notify_email' => ['nullable', 'boolean'],
        ]);

        // Whitelist filter keys to avoid storing junk.
        $allowed = ['q', 'category', 'tag', 'free', 'license', 'format', 'min_price', 'max_price', 'sort'];
        $filters = array_intersect_key((array) ($data['filters'] ?? []), array_flip($allowed));

        $search = SavedSearch::create([
            'user_id' => $request->user()->id,
            'name' => $data['name'],
            'filters' => $filters,
            'notify_email' => $request->boolean('notify_email'),
            'last_notified_at' => now(),
        ]);

        return back()->with('status', __('Пошук «:name» збережено.', ['name' => $search->name]));
    }

    public function destroy(Request $request, SavedSearch $savedSearch)
    {
        abort_unless($savedSearch->user_id === $request->user()->id, 403);
        $savedSearch->delete();
        return back()->with('status', __('Збережений пошук видалено.'));
    }
}
