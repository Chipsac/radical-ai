<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Role;

class InvitationController extends Controller
{
    public function show(string $token)
    {
        $invitation = OrganizationInvitation::where('token', $token)->firstOrFail();

        abort_unless($invitation->isValid(), 410, 'This invitation link has expired or already been accepted.');

        return view('invitations.accept', [
            'invitation' => $invitation,
            'organization' => $invitation->organization,
        ]);
    }

    public function accept(Request $request, string $token)
    {
        $invitation = OrganizationInvitation::where('token', $token)->firstOrFail();
        abort_unless($invitation->isValid(), 410, 'This invitation link has expired or already been accepted.');

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($request, $invitation) {
            $user = User::create([
                'name' => $request->name,
                'email' => $invitation->email,
                'password' => Hash::make($request->password),
                'organization_id' => $invitation->organization_id,
                'role' => $invitation->role,
                'onboarding_completed_at' => now(),
                'onboarding_step' => 3,
            ]);

            $role = Role::firstOrCreate(['name' => $invitation->role]);
            $user->assignRole($role);

            Employee::create([
                'organization_id' => $invitation->organization_id,
                'user_id' => $user->id,
                'job_title' => ucfirst($invitation->role),
                'department' => 'Operations',
                'status' => 'active',
                'start_date' => now()->toDateString(),
            ]);

            $invitation->update(['accepted_at' => now()]);

            return $user;
        });

        Auth::login($user);

        return redirect()->route('home')->with('status', 'Welcome to '.$invitation->organization->name.'!');
    }
}
