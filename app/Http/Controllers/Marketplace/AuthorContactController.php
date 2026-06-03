<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Mail\RenderedTemplateMail;
use App\Models\User;
use App\Services\EmailTemplateRenderer;
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
            $rendered = app(EmailTemplateRenderer::class)->render('author_contact', [
                'user' => [
                    'name' => e($sender->name),
                    'email' => e($sender->email),
                    'username' => e($sender->username ?? ''),
                    'display_name' => e($sender->displayName()),
                    'locale' => $sender->locale ?: app()->getLocale(),
                ],
                'contact' => [
                    'subject' => e($data['subject']),
                    'message' => nl2br(e($data['message'])),
                    'sender_name' => e($sender->displayName()),
                ],
            ], $author->locale ?: app()->getLocale());

            Mail::to($author->email, $author->name)->send(
                new RenderedTemplateMail(
                    $rendered['subject'],
                    $rendered['body'],
                    $sender->email,
                    $sender->name
                )
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
