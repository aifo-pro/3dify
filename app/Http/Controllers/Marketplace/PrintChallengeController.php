<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\PrintChallenge;
use App\Models\PrintChallengeEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PrintChallengeController extends Controller
{
    public function index()
    {
        $challenges = PrintChallenge::query()
            ->where('is_active', true)
            ->withCount(['entries' => fn ($q) => $q->where('status', 'approved')])
            ->orderByDesc('starts_at')
            ->paginate(12);

        return view('marketplace.challenges.index', compact('challenges'));
    }

    public function show(PrintChallenge $challenge)
    {
        abort_unless($challenge->is_active, 404);

        $entries = $challenge->entries()
            ->where('status', 'approved')
            ->with('user')
            ->orderByDesc('votes')
            ->orderByDesc('created_at')
            ->paginate(18);

        $userEntry = auth()->check()
            ? $challenge->entries()->where('user_id', auth()->id())->first()
            : null;

        return view('marketplace.challenges.show', compact('challenge', 'entries', 'userEntry'));
    }

    public function enter(Request $request, PrintChallenge $challenge)
    {
        abort_unless($challenge->isOpen(), 422, 'Challenge is closed.');
        abort_if(
            $challenge->entries()->where('user_id', $request->user()->id)->exists(),
            422, 'Already entered.'
        );

        $data = $request->validate([
            'description' => ['nullable', 'string', 'max:1000'],
            'image'       => ['required', 'image', 'max:8192'],
        ]);

        $path = $request->file('image')->store('challenges/'.$challenge->id, 'public');

        $challenge->entries()->create([
            'user_id'     => $request->user()->id,
            'image_path'  => $path,
            'description' => $data['description'] ?? null,
            'status'      => 'pending',
        ]);

        return back()->with('status', 'Вашу роботу відправлено на перевірку!');
    }

    public function vote(Request $request, PrintChallengeEntry $entry)
    {
        abort_unless($entry->challenge->isOpen(), 422, 'Voting closed.');
        abort_unless($entry->status === 'approved', 422);

        $userId = $request->user()->id;

        $existing = DB::table('print_challenge_votes')
            ->where('entry_id', $entry->id)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            DB::table('print_challenge_votes')
                ->where('entry_id', $entry->id)
                ->where('user_id', $userId)
                ->delete();
            $entry->decrement('votes');
            return response()->json(['voted' => false, 'votes' => $entry->fresh()->votes]);
        }

        DB::table('print_challenge_votes')->insert([
            'entry_id'   => $entry->id,
            'user_id'    => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $entry->increment('votes');

        return response()->json(['voted' => true, 'votes' => $entry->fresh()->votes]);
    }
}
