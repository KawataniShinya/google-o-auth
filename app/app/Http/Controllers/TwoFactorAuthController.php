<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FAQRCode\Google2FA;

class TwoFactorAuthController extends Controller
{
    /**
     * @return \Inertia\Response
     */
    public function show(): \Inertia\Response
    {
        return Inertia::render('Auth/TwoFactorAuthentication');
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function enableTwoFactor(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $google2fa = new Google2FA();

            $QR_Image = null;
            $secret = null;
            if (is_null($user->is_enable_google2fa)) {
                $secret = $google2fa->generateSecretKey();
                $user->google2fa_secret = $secret;
                $user->save();

                $QR_Image = $google2fa->getQRCodeInline(
                    config('app.name'),
                    $user->email,
                    $secret
                );
            }
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to generate 2FA secret. Please try again.'], 500);
        }

        return response()->json(['QR_Image' => $QR_Image, 'secret' => $secret]);
    }

    /**
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws ValidationException
     * @throws SecretKeyTooShortException
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'two_factor_code' => 'required',
        ]);

        $user = Auth::user();
        $google2fa = new Google2FA();

        if ($google2fa->verifyKey($user->google2fa_secret, $request->two_factor_code)) {
            $request->session()->put('two_factor_passed', true);
            if (is_null($user->is_enable_google2fa)) {
                $user->is_enable_google2fa = true;
                $user->save();
            }
            return redirect()->intended('dashboard');
        }

        throw ValidationException::withMessages([
            'two_factor_code' => trans('auth.failed'),
        ]);
    }

    /**
     * Reset the two-factor authentication secret for the user.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function resetSecret(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            $user->forceFill([
                'google2fa_secret' => (new Google2FA())->generateSecretKey(),
                'is_enable_google2fa' => null, // Disable two-factor authentication after resetting secret
            ])->save();
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to generate 2FA secret. Please try again.'], 500);
        }

        return response()->json(['message' => 'Two-factor authentication secret has been reset.']);
    }
}
