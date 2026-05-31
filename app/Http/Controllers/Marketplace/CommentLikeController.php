<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\ProductComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommentLikeController extends Controller
{
    public function toggle(Request $request, ProductComment $comment)
    {
        $userId = $request->user()->id;

        $existing = DB::table('product_comment_likes')
            ->where('comment_id', $comment->id)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            DB::table('product_comment_likes')
                ->where('comment_id', $comment->id)
                ->where('user_id', $userId)
                ->delete();
            $comment->decrement('likes_count');
            return response()->json(['liked' => false, 'count' => $comment->fresh()->likes_count]);
        }

        DB::table('product_comment_likes')->insert([
            'comment_id' => $comment->id,
            'user_id'    => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $comment->increment('likes_count');

        return response()->json(['liked' => true, 'count' => $comment->fresh()->likes_count]);
    }
}
