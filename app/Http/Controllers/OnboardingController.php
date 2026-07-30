<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Invitation;
use App\Models\Organization;
use App\Services\DemoDataSeeder;
use Illuminate\Http\Request;

/**
 * Post-signup guided setup. Progress lives on the organization so a user can
 * close the tab and resume exactly where they left off, on any device.
 */
class OnboardingController extends Controller
{
    public function show(Request $request)
    {
        $org = $request->user()->organization;

        if ($org->hasCompletedOnboarding()) {
            return redirect()->route('home');
        }

        return view('onboarding.wizard', [
            'organization' => $org,
            'step' => $org->onboarding_step,
            'steps' => Organization::ONBOARDING_STEPS,
            'progress' => $org->onboardingProgress(),
            'invitations' => Invitation::latest()->get(),
        ]);
    }

    /** Step 1 — tell us about the organisation. */
    public function saveProfile(Request $request)
    {
        $data = $request->validate([
            'industry' => ['required', 'string', 'max:80'],
            'team_size' => ['required', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'size:2'],
        ]);

        $request->user()->organization->update($data + ['onboarding_step' => 'team']);

        return redirect()->route('onboarding.show');
    }

    /** Step 2 — invite colleagues (skippable). */
    public function saveTeam(Request $request)
    {
        $request->user()->organization->update(['onboarding_step' => 'data']);

        return redirect()->route('onboarding.show');
    }

    /**
     * Step 3 — choose a starting point. Sample data lets someone evaluate the
     * product immediately; a clean workspace suits a real rollout.
     */
    public function saveData(Request $request, DemoDataSeeder $seeder)
    {
        $data = $request->validate([
            'starting_point' => ['required', 'in:sample,blank'],
        ]);

        $org = $request->user()->organization;

        if ($data['starting_point'] === 'sample') {
            $seeder->seedFor($org, $request->user());
        }

        $org->update([
            'onboarding_step' => 'done',
            'onboarding_completed_at' => now(),
        ]);

        AuditLog::record('onboarding.completed', $request->user(), $org, [
            'starting_point' => $data['starting_point'],
        ]);

        return redirect()->route('home')->with('status', match ($data['starting_point']) {
            'sample' => 'Your workspace is ready, pre-filled with sample data you can safely explore or delete.',
            default => 'Your workspace is ready. Start by creating your first deal or task.',
        });
    }

    /** Let people bail out of the wizard and get to the app. */
    public function skip(Request $request)
    {
        $request->user()->organization->update([
            'onboarding_step' => 'done',
            'onboarding_completed_at' => now(),
        ]);

        return redirect()->route('home')
            ->with('status', 'Setup skipped — you can finish it any time from Settings.');
    }
}
