<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\MailRuntime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );
        } catch (Throwable $exception) {
            Log::error('Password reset link delivery failed.', [
                'email' => $request->input('email'),
                'message' => $exception->getMessage(),
                'mail' => MailRuntime::context(),
            ]);

            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => __('Не вдалося надіслати лист для відновлення пароля. Перевірте налаштування пошти або спробуйте ще раз.'),
                ]);
        }

        if ($status == Password::RESET_LINK_SENT) {
            Log::info('Password reset link delivery accepted.', [
                'email' => $request->input('email'),
                'mail' => MailRuntime::context(),
            ]);

            return back()->with('status', __($status));
        }

        return back()->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}
