<?php

namespace App\Assistant\Tools;

use App\Assistant\Tool;
use App\Models\Invitation;
use App\Models\User;
use App\Support\Modules;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Str;

/**
 * "Create a new account for me" — which in a multi-tenant product means
 * inviting someone to this workspace, not creating a login directly.
 *
 * Deliberately an invitation rather than a user row: the person sets their own
 * password from a link sent to an address they control. Letting the assistant
 * mint credentials would create an account whose password somebody else chose,
 * which is a worse outcome than the convenience is worth.
 */
class InviteTeamMember extends Tool
{
    public function name(): string
    {
        return 'invite_team_member';
    }

    public function description(): string
    {
        return 'Invite someone to this workspace by email. They receive a link and '
            .'set their own password. Call this for "add someone", "create an '
            .'account for", "get X set up". Managers and admins only.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'email' => ['type' => 'string', 'format' => 'email'],
                'job_title' => ['type' => 'string', 'description' => 'Their role in the company, if mentioned. Not their permission level.'],
                'role' => [
                    'type' => 'string',
                    'enum' => ['employee', 'manager', 'admin'],
                    'description' => 'Default employee. Only choose higher if the person explicitly said so.',
                ],
                'modules' => [
                    'type' => 'array',
                    'items' => ['type' => 'string', 'enum' => [Modules::CRM, Modules::TASKS, Modules::HR]],
                    'description' => 'Which parts of the product they can open.',
                ],
            ],
            'required' => ['email'],
            'additionalProperties' => false,
        ];
    }

    public function isWrite(): bool
    {
        return true;
    }

    public function authorize(User $user): bool
    {
        return $user->isManager();
    }

    public function describeCall(array $arguments): string
    {
        $modules = implode(', ', array_map(
            fn ($m) => Modules::label($m),
            $arguments['modules'] ?? [Modules::TASKS]
        ));

        return sprintf(
            'Invite %s as %s, with access to %s',
            $arguments['email'] ?? '?',
            $arguments['role'] ?? 'employee',
            $modules ?: 'nothing yet'
        );
    }

    public function run(User $user, array $arguments): array
    {
        if (! $user->isManager()) {
            throw new AuthorizationException('Only managers and admins can invite people.');
        }

        $email = strtolower(trim($arguments['email']));

        // Scoped query: an address already used in a *different* organisation is
        // not this workspace's business, and reporting it would leak that the
        // person exists elsewhere in the product.
        if (User::where('email', $email)->exists()) {
            return ['error' => 'Someone with that email is already in this workspace.'];
        }

        // The assistant must not be a way to escalate. A manager cannot mint an
        // admin, matching the rule the team screen enforces.
        $role = $arguments['role'] ?? 'employee';

        if ($role === 'admin' && ! $user->isAdmin()) {
            throw new AuthorizationException('Only an admin can invite another admin.');
        }

        $invitation = Invitation::create([
            'email' => $email,
            'job_title' => $arguments['job_title'] ?? null,
            'role' => $role,
            'modules' => Modules::sanitise($arguments['modules'] ?? [Modules::TASKS]),
            'token' => Str::random(64),
            'invited_by' => $user->id,
            'expires_at' => now()->addDays(7),
        ]);

        return [
            'ok' => true,
            'invited' => $invitation->email,
            'role' => $invitation->role,
            'expires' => $invitation->expires_at->toDateString(),
            'note' => 'They have been emailed a link to set their own password.',
        ];
    }
}
