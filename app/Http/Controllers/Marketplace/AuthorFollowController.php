<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AuthorFollowedNotification;
use Illuminate\Http\Request;

class AuthorFollowController extends Controller
{
    public function store(Request $request, string $user)
    {
        $author = $this->resolveAuthor($user);
        $follower = $request->user();

        abort_if($author->id === $follower->id, 422, 'Cannot follow yourself.');

        $alreadyFollowing = $author->followers()->where('follower_id', $follower->id)->exists();
        $author->followers()->syncWithoutDetaching([$follower->id]);

        if (! $alreadyFollowing) {
            $author->notify(new AuthorFollowedNotification($follower));
        }

        if ($request->wantsJson()) {
            return response()->json([
                'following' => true,
                'count' => $author->followers()->count(),
            ]);
        }

        return back()->with('status', __('Ви підписалися на автора.'));
    }

    public function destroy(Request $request, string $user)
    {
        $author = $this->resolveAuthor($user);
        $follower = $request->user();

        $author->followers()->detach($follower->id);

        if ($request->wantsJson()) {
            return response()->json([
                'following' => false,
                'count' => $author->followers()->count(),
            ]);
        }

        return back()->with('status', __('Ви відписалися від автора.'));
    }

    private function resolveAuthor(string $key): User
    {
        return User::query()
            ->where('username', $key)
            ->orWhere('id', $key)
            ->firstOrFail();
    }
}
