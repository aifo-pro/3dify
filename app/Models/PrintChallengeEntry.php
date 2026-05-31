<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrintChallengeEntry extends Model
{
    protected $fillable = ['challenge_id', 'user_id', 'make_id', 'image_path', 'description', 'votes', 'status'];

    protected $casts = ['votes' => 'integer'];

    public function challenge()
    {
        return $this->belongsTo(PrintChallenge::class, 'challenge_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function make()
    {
        return $this->belongsTo(ProductMake::class, 'make_id');
    }

    public function hasVotedBy(?User $user): bool
    {
        if (! $user) return false;
        return \Illuminate\Support\Facades\DB::table('print_challenge_votes')
            ->where('entry_id', $this->id)
            ->where('user_id', $user->id)
            ->exists();
    }
}
