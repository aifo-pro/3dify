<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA;
    }

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function qrSvg(string $companyName, string $accountIdentifier, string $secret): string
    {
        $url = $this->google2fa->getQRCodeUrl($companyName, $accountIdentifier, $secret);
        $renderer = new ImageRenderer(new RendererStyle(280, 1), new SvgImageBackEnd);
        $svg = (new Writer($renderer))->writeString($url);

        // Strip the XML declaration and remove fixed width/height so the SVG
        // becomes responsive — the parent container controls the size, and the
        // viewBox keeps it crisp on any zoom.
        $svg = preg_replace('/<\?xml.*?\?>/', '', $svg);
        $svg = preg_replace('/\s(width|height)="[^"]*"/', '', $svg, 2);
        $svg = preg_replace('/<svg\s/', '<svg style="display:block;width:100%;height:auto" ', $svg, 1);

        return trim($svg);
    }

    public function verify(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, preg_replace('/\s+/', '', $code));
    }

    /**
     * @return list<string>
     */
    public function generateRecoveryCodes(): array
    {
        return collect(range(1, 8))
            ->map(fn () => Str::lower(Str::random(5)).'-'.Str::lower(Str::random(5)))
            ->all();
    }

    public function enable(User $user, string $secret, string $code): bool
    {
        if (! $this->verify($secret, $code)) {
            return false;
        }

        $user->forceFill([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($this->generateRecoveryCodes(), JSON_THROW_ON_ERROR)),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return true;
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    public function decryptedSecret(User $user): ?string
    {
        if (! $user->two_factor_secret) {
            return null;
        }
        return Crypt::decryptString($user->two_factor_secret);
    }

    /**
     * @return list<string>
     */
    public function decryptedRecoveryCodes(User $user): array
    {
        if (! $user->two_factor_recovery_codes) {
            return [];
        }
        return json_decode(Crypt::decryptString($user->two_factor_recovery_codes), true) ?? [];
    }

    public function consumeRecoveryCode(User $user, string $code): bool
    {
        $codes = $this->decryptedRecoveryCodes($user);
        $code = trim($code);
        if (! in_array($code, $codes, true)) {
            return false;
        }
        $codes = array_values(array_filter($codes, fn ($c) => $c !== $code));
        $user->forceFill([
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($codes, JSON_THROW_ON_ERROR)),
        ])->save();
        return true;
    }
}
