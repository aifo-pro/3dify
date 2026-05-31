<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SendOnboardingTipsEmail;
use App\Mail\DatabaseTemplateMail;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name'        => $request->name,
            'email'       => $request->email,
            'password'    => Hash::make($request->password),
            'referred_by' => session('referral_user_id'),
        ]);
        session()->forget(['referral_code', 'referral_user_id']);

        event(new Registered($user));

        Mail::to($user)->queue(new DatabaseTemplateMail('registration', $user, [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]));

        // Send onboarding tips 2 days after registration
        SendOnboardingTipsEmail::dispatch($user->id)->delay(now()->addDays(2));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
