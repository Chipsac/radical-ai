<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;

class InvitationController extends Controller
{
    public function __construct(private TenantContext $tenant) {}

    public function index(Request $request)
    {
        return view('team.index', [
            'members' => User::where('organization_id', $request->user()->organization_id)
                ->with('employee')->orderBy('name')->get(),
            'invitations' => Invitation::with('inviter')->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'in:admin,manager,employee'],
            'job_title' => ['nullable', 'string', 'max:120'],
        ]);

        $orgId = $request->user()->organization_id;

        if (User::where('organization_id', $orgId)->where('email', $data['email'])->exists()) {
            return back()->withErrors(['email' => 'That person is already in your workspace.']);
        }

        // Re-inviting simply refreshes the existing pending invite.
        $invitation = Invitation::updateOrCreate(
            ['organization_id' => $orgId, 'email' => $data['email'], 'accepted_at' => null],
            [
                'role' => $data['role'],
                'job_title' => $data['job_title'] ?? null,
                'token' => Invitation::generateToken(),
                'invited_by' => $request->user()->id,
                'expires_at' => now()->addDays(7),
            ]
        );

        $this->sendInvitationEmail($invitation, $request->user()->organization);

        AuditLog::record('team.invited', $request->user(), $invitation, [
            'email' => $data['email'],
            'role' => $data['role'],
        ]);

        return back()->with('status', "Invitation sent to {$data['email']}.");
    }

    public function destroy(Request $request, Invitation $invitation)
    {
        abort_unless($invitation->organization_id === $request->user()->organization_id, 403);

        $invitation->delete();

        AuditLog::record('team.invite_revoked', $request->user(), null, ['email' => $invitation->email]);

        return back()->with('status', 'Invitation revoked.');
    }

    /**
     * Public landing page for an invite link. Runs without tenant context,
     * so the invitation is looked up across tenants by its unguessable token.
     */
    public function accept(string $token)
    {
        $invitation = $this->tenant->withoutTenancy(
            fn () => Invitation::where('token', $token)->first()
        );

        if (! $invitation || ! $invitation->isPending()) {
            return view('auth.invitation-invalid', [
                'reason' => $invitation?->accepted_at
                    ? 'This invitation has already been used.'
                    : 'This invitation link has expired or is no longer valid.',
            ]);
        }

        $organization = $this->tenant->withoutTenancy(
            fn () => Organization::find($invitation->organization_id)
        );

        return view('auth.invitation-accept', [
            'invitation' => $invitation,
            'organization' => $organization,
        ]);
    }

    /**
     * Create the invited user inside the inviting organization.
     */
    public function complete(Request $request, string $token)
    {
        $invitation = $this->tenant->withoutTenancy(
            fn () => Invitation::where('token', $token)->first()
        );

        abort_if(! $invitation || ! $invitation->isPending(), 410, 'This invitation is no longer valid.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($data, $invitation) {
            return $this->tenant->asTenant($invitation->organization_id, function () use ($data, $invitation) {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $invitation->email,
                    'password' => Hash::make($data['password']),
                    'organization_id' => $invitation->organization_id,
                    'role' => $invitation->role,
                    'email_verified_at' => now(), // proven by receiving the invite email
                ]);

                $user->assignRole($invitation->role);

                Employee::create([
                    'organization_id' => $invitation->organization_id,
                    'user_id' => $user->id,
                    'job_title' => $invitation->job_title ?? 'Team member',
                    'start_date' => now(),
                    'status' => 'active',
                ]);

                $invitation->forceFill(['accepted_at' => now()])->save();

                return $user;
            });
        });

        AuditLog::record('team.invite_accepted', $user, $user);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')
            ->with('status', 'Welcome aboard! Your account is ready.');
    }

    private function sendInvitationEmail(Invitation $invitation, Organization $organization): void
    {
        $url = $invitation->acceptUrl();

        // Plain-text mail keeps the prototype dependency-free; with MAIL_MAILER=log
        // the message lands in the application log instead of being sent.
        Mail::raw(
            "You've been invited to join {$organization->name} on Radical AI.\n\n".
            "Accept your invitation:\n{$url}\n\n".
            "This link expires on {$invitation->expires_at->format('j M Y')}.",
            function ($message) use ($invitation, $organization) {
                $message->to($invitation->email)
                    ->subject("You're invited to join {$organization->name} on Radical AI");
            }
        );
    }
}
