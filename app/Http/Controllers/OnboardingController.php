<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Invitation;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\TaskCategory;
use App\Services\DemoDataSeeder;
use App\Support\Modules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Post-signup guided setup, following the customer journey:
 *
 *   3. Add personal information
 *   4. Set up organisation (name · plan · pipeline · leave · categories)
 *   5. Add people & assign roles, which sends the invitations (6)
 *
 * Progress lives on the organization so a user can close the tab and resume
 * exactly where they left off, on any device.
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
            'leaveTypes' => LeaveType::orderBy('id')->get(),
            'categories' => TaskCategory::orderBy('id')->get(),
        ]);
    }

    /** Step 3 — personal information. */
    public function savePersonal(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
        ]);

        $user = $request->user();

        $user->forceFill(['name' => $data['name']])->save();

        // Job title and phone live on the employee record — that is what HR
        // and the directory read from.
        if ($employee = $user->employee) {
            $employee->update(array_filter([
                'job_title' => $data['job_title'] ?? null,
                'phone' => $data['phone'] ?? null,
            ]));
        }

        $user->organization->update(['onboarding_step' => 'organisation']);

        return redirect()->route('onboarding.show');
    }

    /**
     * Step 4 — organisation setup: name, plan, pipeline stages, leave types
     * and task categories. Everything here has a sensible default, so a user
     * in a hurry can accept and move on.
     */
    public function saveOrganisation(Request $request, DemoDataSeeder $seeder)
    {
        $data = $request->validate([
            'organization_name' => ['required', 'string', 'max:120'],
            'industry' => ['required', 'string', 'max:80'],
            'team_size' => ['required', 'string', 'max:20'],
            'plan_tier' => ['required', 'in:free,starter,pro'],
            'leave_types' => ['nullable', 'array'],
            'leave_types.*.name' => ['nullable', 'string', 'max:60'],
            'leave_types.*.days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'categories' => ['nullable', 'array'],
            'categories.*.name' => ['nullable', 'string', 'max:60'],
            'categories.*.color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'starting_point' => ['required', 'in:sample,blank'],
        ]);

        $org = $request->user()->organization;

        DB::transaction(function () use ($data, $org, $request, $seeder) {
            $org->update([
                'name' => $data['organization_name'],
                'industry' => $data['industry'],
                'team_size' => $data['team_size'],
                'plan_tier' => $data['plan_tier'],
                'onboarding_step' => 'team',
            ]);

            $this->syncLeaveTypes($org, $data['leave_types'] ?? []);
            $this->syncCategories($org, $data['categories'] ?? []);

            if ($data['starting_point'] === 'sample') {
                $seeder->seedFor($org, $request->user());
            }
        });

        return redirect()->route('onboarding.show');
    }

    /** Step 5/6 — people, roles and module access; invitations are sent from here. */
    public function saveTeam(Request $request)
    {
        $org = $request->user()->organization;

        $org->update([
            'onboarding_step' => 'done',
            'onboarding_completed_at' => now(),
        ]);

        AuditLog::record('onboarding.completed', $request->user(), $org, [
            'invitations_sent' => Invitation::count(),
        ]);

        return redirect()->route('home')
            ->with('status', 'Your workspace is ready. Anyone you invited will get an email with a link to join.');
    }

    /** Let people bail out of the wizard and get straight to the app. */
    public function skip(Request $request)
    {
        $request->user()->organization->update([
            'onboarding_step' => 'done',
            'onboarding_completed_at' => now(),
        ]);

        return redirect()->route('home')
            ->with('status', 'Setup skipped — you can finish it any time from Settings.');
    }

    /**
     * Replace the org's leave types with what the user submitted. Blank rows
     * are ignored so the user can simply leave the extras empty.
     */
    private function syncLeaveTypes(Organization $org, array $rows): void
    {
        $rows = collect($rows)->filter(fn ($r) => filled($r['name'] ?? null));

        if ($rows->isEmpty()) {
            return; // keep the defaults created at signup
        }

        LeaveType::query()->delete();

        foreach ($rows as $row) {
            LeaveType::create([
                'organization_id' => $org->id,
                'name' => $row['name'],
                'paid' => ! str_contains(strtolower($row['name']), 'unpaid'),
                'default_allowance_days' => (int) ($row['days'] ?? 0),
            ]);
        }
    }

    private function syncCategories(Organization $org, array $rows): void
    {
        $rows = collect($rows)->filter(fn ($r) => filled($r['name'] ?? null));

        if ($rows->isEmpty()) {
            return;
        }

        TaskCategory::query()->delete();

        foreach ($rows as $row) {
            TaskCategory::create([
                'organization_id' => $org->id,
                'name' => $row['name'],
                'color' => $row['color'] ?? '#E0A44E',
                'prefix' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $row['name']) ?: 'GEN', 0, 3)),
            ]);
        }
    }
}
