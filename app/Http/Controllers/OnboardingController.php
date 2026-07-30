<?php

namespace App\Http\Controllers;

use App\Models\OrganizationInvitation;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->hasCompletedOnboarding()) {
            return redirect()->route('home');
        }

        return view('onboarding.index', [
            'user' => $user,
            'organization' => $user->organization,
            'step' => $user->onboarding_step ?? 1,
        ]);
    }

    public function processStep(Request $request)
    {
        $user = $request->user();
        $step = $request->integer('step', 1);

        if ($step === 1) {
            $data = $request->validate([
                'industry' => ['required', 'string', 'max:100'],
                'size_range' => ['required', 'string', 'max:50'],
            ]);

            $user->organization->update($data);
            $user->update(['onboarding_step' => 2]);

            return back()->with('status', 'Step 1 saved.');
        }

        if ($step === 2) {
            $invites = array_filter($request->input('invites', []));

            foreach ($invites as $invite) {
                if (filter_var($invite['email'] ?? null, FILTER_VALIDATE_EMAIL)) {
                    OrganizationInvitation::create([
                        'organization_id' => $user->organization_id,
                        'email' => strtolower(trim($invite['email'])),
                        'role' => in_array($invite['role'] ?? 'employee', ['admin', 'manager', 'employee'])
                            ? $invite['role']
                            : 'employee',
                        'invited_by' => $user->id,
                    ]);
                }
            }

            $user->update(['onboarding_step' => 3]);

            return back()->with('status', 'Team invitations generated.');
        }

        if ($step === 3) {
            $user->markOnboardingComplete();

            return redirect()->route('home')->with('status', 'Welcome to Radical AI! Your workspace is ready.');
        }

        return back();
    }
}
