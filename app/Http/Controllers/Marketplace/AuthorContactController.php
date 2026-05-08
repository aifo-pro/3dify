<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AuthorContactController extends Controller
{
    public function store(Request $request, string $user)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $author = User::query()
            ->where('username', $user)
            ->orWhere('id', $user)
            ->firstOrFail();

        $sender = $request->user();
        abort_if($author->id === $sender->id, 422, 'Cannot contact yourself.');
        abort_if(! ($author->contact_enabled ?? true), 404);

        try {
            Mail::raw(
                "From: {$sender->name} <{$sender->email}>\n\n".$data['message'],
                function ($message) use ($author, $sender, $data) {
                    $message->to($author->email, $author->name)
                        ->replyTo($sender->email, $sender->name)
                        ->subject('[3Dify] '.$data['subject']);
                }
            );
        } catch (\Throwable $e) {
            Log::warning('Author contact email failed', [
                'author_id' => $author->id,
                'sender_id' => $sender->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['contact' => __('Не вдалося надіслати лист. Спробуйте пізніше.')]);
        }

        return back()->with('status', __('Ваш лист надіслано автору.'));
    }
}
