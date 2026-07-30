<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\TotpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * The second step of login for users with 2FA on. The first factor has
 * already been checked; the user id is held in the session, NOT logged in,
 * until a valid TOTP or recovery code is supplied.
 */
class TwoFactorChallengeController extends Controller
{
    public function __construct(private TotpService $totp) {}

    public function show(Request $request)
    {
        if (! $request->session()->has('2fa.user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request)
    {
        $userId = $request->session()->get('2fa.user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = User::findOrFail($userId);
        $code = trim($request->input('code'));

        $passed = $this->totp->verify(decrypt($user->two_factor_secret), $code)
            || $user->useRecoveryCode($code);

        if (! $passed) {
            AuditLog::record('auth.2fa_failed', $user, $user);

            throw ValidationException::withMessages([
                'code' => 'That code was not valid. Try again, or use one of your recovery codes.',
            ]);
        }

        $request->session()->forget(['2fa.user_id', '2fa.remember']);

        Auth::login($user, (bool) $request->session()->pull('2fa.remember', false));
        $request->session()->regenerate();

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        AuditLog::record('auth.login', $user, $user, ['method' => '2fa']);

        return redirect()->intended(route('home'));
    }
}
