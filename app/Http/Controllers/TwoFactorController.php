<?php

namespace App\Http\Controllers;

use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorController extends Controller
{
    public function show(Request $request, TwoFactorService $tfa)
    {
        $user = $request->user();
        $confirmed = (bool) $user->two_factor_confirmed_at;

        $pendingSecret = session('2fa.pending_secret');
        if (! $confirmed && ! $pendingSecret) {
            $pendingSecret = $tfa->generateSecret();
            session(['2fa.pending_secret' => $pendingSecret]);
        }

        $qr = null;
        $manual = null;
        if (! $confirmed) {
            $qr = $tfa->qrSvg(config('app.name'), $user->email, $pendingSecret);
            $manual = $pendingSecret;
        }

        return view('marketplace.account.two-factor', [
            'confirmed' => $confirmed,
            'qr' => $qr,
            'manual' => $manual,
            'recoveryCodes' => $confirmed ? $tfa->decryptedRecoveryCodes($user) : [],
        ]);
    }

    public function enable(Request $request, TwoFactorService $tfa)
    {
        $data = $request->validate(['code' => ['required', 'string', 'min:6', 'max:8']]);
        $secret = (string) session('2fa.pending_secret');
        if (! $secret) {
            return back()->withErrors(['code' => __('Сесію втрачено, спробуйте знову.')]);
        }

        $ok = $tfa->enable($request->user(), $secret, $data['code']);
        if (! $ok) {
            return back()->withErrors(['code' => __('Невірний код. Перевірте годинник пристрою.')]);
        }

        session()->forget('2fa.pending_secret');
        return redirect()->route('two-factor.show')->with('status', __('Двофакторну автентифікацію увімкнено. Збережіть резервні коди!'));
    }

    public function disable(Request $request, TwoFactorService $tfa)
    {
        $request->validate(['password' => ['required', 'current_password']]);
        $tfa->disable($request->user());
        return back()->with('status', __('Двофакторну автентифікацію вимкнено.'));
    }

    public function regenerateRecovery(Request $request, TwoFactorService $tfa)
    {
        $user = $request->user();
        abort_unless($user->two_factor_confirmed_at, 404);
        $codes = $tfa->generateRecoveryCodes();
        $user->forceFill([
            'two_factor_recovery_codes' => \Illuminate\Support\Facades\Crypt::encryptString(json_encode($codes, JSON_THROW_ON_ERROR)),
        ])->save();
        return back()->with('status', __('Резервні коди оновлено.'));
    }

    /**
     * Show the challenge after the password step.
     */
    public function challenge()
    {
        $userId = session('2fa.challenge_user_id');
        if (! $userId) {
            return redirect()->route('login');
        }
        return view('auth.two-factor-challenge');
    }

    public function challengeSubmit(Request $request, TwoFactorService $tfa)
    {
        $userId = (int) session('2fa.challenge_user_id');
        $remember = (bool) session('2fa.challenge_remember', false);
        if (! $userId) {
            return redirect()->route('login');
        }

        $user = \App\Models\User::find($userId);
        if (! $user) {
            session()->forget(['2fa.challenge_user_id', '2fa.challenge_remember']);
            return redirect()->route('login');
        }

        $data = $request->validate([
            'code' => ['required_without:recovery_code', 'nullable', 'string'],
            'recovery_code' => ['required_without:code', 'nullable', 'string'],
        ]);

        $secret = $tfa->decryptedSecret($user);
        $passed = false;
        if (! empty($data['code']) && $secret) {
            $passed = $tfa->verify($secret, $data['code']);
        }
        if (! $passed && ! empty($data['recovery_code'])) {
            $passed = $tfa->consumeRecoveryCode($user, $data['recovery_code']);
        }
        if (! $passed) {
            return back()->withErrors(['code' => __('Невірний код.')]);
        }

        Auth::login($user, $remember);
        session()->forget(['2fa.challenge_user_id', '2fa.challenge_remember']);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
