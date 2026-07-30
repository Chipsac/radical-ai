<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\TotpService;
use Illuminate\Http\Request;

class TwoFactorController extends Controller
{
    public function __construct(private TotpService $totp) {}

    /**
     * Show the 2FA panel. Generating the secret here (into the session, not
     * the user record) means an abandoned setup leaves nothing behind.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        if (! $user->hasTwoFactorEnabled() && ! $request->session()->has('2fa.pending_secret')) {
            $request->session()->put('2fa.pending_secret', $this->totp->generateSecret());
        }

        $secret = $request->session()->get('2fa.pending_secret');

        return view('auth.two-factor', [
            'enabled' => $user->hasTwoFactorEnabled(),
            'secret' => $secret,
            'otpauthUri' => $secret
                ? $this->totp->provisioningUri($secret, $user->email, config('app.name'))
                : null,
            'recoveryCodes' => $request->session()->pull('2fa.recovery_codes'),
        ]);
    }

    /**
     * Confirm setup: the user must prove they can generate a valid code
     * before 2FA is switched on, otherwise they'd lock themselves out.
     */
    public function confirm(Request $request)
    {
        $request->validate(['code' => ['required', 'string']]);

        $user = $request->user();
        $secret = $request->session()->get('2fa.pending_secret');

        if (! $secret) {
            return back()->withErrors(['code' => 'Setup expired — please start again.']);
        }

        if (! $this->totp->verify($secret, $request->input('code'))) {
            return back()->withErrors(['code' => 'That code was not valid. Check your authenticator app and try again.']);
        }

        $recoveryCodes = $this->totp->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $request->session()->forget('2fa.pending_secret');
        $request->session()->put('2fa.recovery_codes', $recoveryCodes);

        AuditLog::record('auth.2fa_enabled', $user, $user);

        return redirect()->route('two-factor.show')
            ->with('status', 'Two-factor authentication is now on. Save your recovery codes.');
    }

    /**
     * Disabling requires the current password — an unattended session
     * shouldn't be able to weaken the account.
     */
    public function destroy(Request $request)
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $user = $request->user();

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        AuditLog::record('auth.2fa_disabled', $user, $user);

        return back()->with('status', 'Two-factor authentication has been turned off.');
    }

    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $user = $request->user();
        $codes = $this->totp->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode($codes)),
        ])->save();

        AuditLog::record('auth.2fa_recovery_regenerated', $user, $user);

        return back()->with('2fa.recovery_codes', $codes)
            ->with('status', 'New recovery codes generated — the old ones no longer work.');
    }
}
