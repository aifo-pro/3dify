<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrintChallenge;
use App\Models\PrintChallengeEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PrintChallengeAdminController extends Controller
{
    public function index()
    {
        $challenges = PrintChallenge::withCount('entries')->latest()->paginate(20);
        return view('admin.challenges.index', compact('challenges'));
    }

    public function create()
    {
        return view('admin.challenges.form', ['challenge' => new PrintChallenge]);
    }

    public function store(Request $request)
    {
        PrintChallenge::create($this->validated($request));
        return redirect()->route('admin.challenges.index')->with('status', 'Челендж створено.');
    }

    public function edit(PrintChallenge $challenge)
    {
        $challenge->loadCount('entries');
        $entries = $challenge->entries()->with('user')->latest()->paginate(20);
        return view('admin.challenges.form', compact('challenge', 'entries'));
    }

    public function update(Request $request, PrintChallenge $challenge)
    {
        $challenge->update($this->validated($request));
        return back()->with('status', 'Збережено.');
    }

    public function moderateEntry(Request $request, PrintChallengeEntry $entry)
    {
        $data = $request->validate(['status' => ['required', 'in:pending,approved,winner,rejected']]);
        $entry->update($data);
        return back()->with('status', 'Статус оновлено.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title_uk'          => ['required', 'string', 'max:200'],
            'title_en'          => ['nullable', 'string', 'max:200'],
            'description_uk'    => ['nullable', 'string'],
            'prize_description' => ['nullable', 'string', 'max:500'],
            'starts_at'         => ['nullable', 'date'],
            'ends_at'           => ['nullable', 'date'],
            'is_active'         => ['nullable'],
        ]);

        return [
            'slug'              => Str::slug($data['title_uk']).'-'.Str::lower(Str::random(4)),
            'title'             => ['uk' => $data['title_uk'], 'en' => $data['title_en'] ?? $data['title_uk']],
            'description'       => ['uk' => $data['description_uk'] ?? '', 'en' => ''],
            'prize_description' => $data['prize_description'] ?? null,
            'starts_at'         => $data['starts_at'] ?? null,
            'ends_at'           => $data['ends_at'] ?? null,
            'is_active'         => $request->boolean('is_active'),
        ];
    }
}
