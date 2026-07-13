<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    /** GET /admin/2fa/status */
    public function status(Request $request)
    {
        return response()->json([
            'enabled' => (bool) $request->user()->twofa_enabled_at,
            'enabled_at' => $request->user()->twofa_enabled_at,
        ]);
    }

    /**
     * POST /admin/2fa/setup — generates a fresh secret (pending until
     * confirmed) and returns the otpauth URI plus an inline QR SVG.
     */
    public function setup(Request $request)
    {
        $user = $request->user();

        if ($user->twofa_enabled_at) {
            return response()->json(['message' => 'Two-factor is already enabled. Disable it first to re-enroll.'], 422);
        }

        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey(32);

        $user->update(['twofa_secret' => $secret, 'twofa_last_ts' => null]);

        $otpauthUri = $google2fa->getQRCodeUrl('uLam Admin', $user->email, $secret);

        $renderer = new ImageRenderer(new RendererStyle(220), new SvgImageBackEnd());
        $qrSvg = (new Writer($renderer))->writeString($otpauthUri);

        return response()->json([
            'secret' => $secret,
            'otpauth_uri' => $otpauthUri,
            'qr_svg' => $qrSvg,
        ]);
    }

    /** POST /admin/2fa/confirm — verifies the first code and switches 2FA on. */
    public function confirm(Request $request)
    {
        $request->validate(['code' => ['required', 'string']]);

        $user = $request->user();

        if (!$user->twofa_secret) {
            throw ValidationException::withMessages(['code' => 'Run setup first.']);
        }
        if ($user->twofa_enabled_at) {
            throw ValidationException::withMessages(['code' => 'Two-factor is already enabled.']);
        }

        $google2fa = new Google2FA();
        $ts = $google2fa->verifyKeyNewer($user->twofa_secret, trim($request->code), (int) ($user->twofa_last_ts ?? 0));

        if ($ts === false) {
            throw ValidationException::withMessages(['code' => 'Incorrect code — check your authenticator app and try again.']);
        }

        $user->update(['twofa_enabled_at' => now(), 'twofa_last_ts' => $ts]);

        return response()->json(['message' => 'Two-factor authentication is now ON.']);
    }

    /** POST /admin/2fa/disable — requires password + a current code. */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (!$user->twofa_enabled_at) {
            return response()->json(['message' => 'Two-factor is not enabled.'], 422);
        }

        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages(['password' => 'Incorrect password.']);
        }

        $google2fa = new Google2FA();
        $ts = $google2fa->verifyKeyNewer($user->twofa_secret, trim($request->code), (int) ($user->twofa_last_ts ?? 0));

        if ($ts === false) {
            throw ValidationException::withMessages(['code' => 'Incorrect code.']);
        }

        $user->update(['twofa_secret' => null, 'twofa_enabled_at' => null, 'twofa_last_ts' => null]);

        return response()->json(['message' => 'Two-factor authentication is OFF.']);
    }
}
