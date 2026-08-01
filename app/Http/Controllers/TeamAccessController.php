<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\Modules;
use Illuminate\Http\Request;

/**
 * Step 10 of the customer journey: a manager decides which parts of the
 * product each team member can open.
 */
class TeamAccessController extends Controller
{
    public function updateModules(Request $request, User $user)
    {
        $this->authoriseSameOrg($request, $user);

        $data = $request->validate([
            'modules' => ['nullable', 'array'],
            'modules.*' => ['string', 'in:'.implode(',', Modules::ALL)],
        ]);

        if ($user->isAdmin()) {
            return back()->withErrors([
                'modules' => 'Admins always have access to every module — that cannot be restricted.',
            ]);
        }

        $before = $user->allowedModules();
        $after = Modules::sanitise($data['modules'] ?? []);

        $user->setModules($after);

        AuditLog::record('team.modules_changed', $request->user(), $user, [
            'member' => $user->email,
            'from' => $before,
            'to' => $after,
        ]);

        return back()->with('status', $after
            ? sprintf('%s can now access: %s.', $user->name, collect($after)->map(fn ($m) => Modules::label($m))->join(', '))
            : sprintf('%s no longer has access to any module.', $user->name));
    }

    public function updateRole(Request $request, User $user)
    {
        $this->authoriseSameOrg($request, $user);

        $data = $request->validate([
            'role' => ['required', 'in:admin,manager,employee'],
        ]);

        // Only an admin may hand out admin, and the last admin must remain.
        if ($data['role'] === 'admin' && ! $request->user()->isAdmin()) {
            return back()->withErrors(['role' => 'Only an admin can promote someone to admin.']);
        }

        if ($user->isAdmin() && $data['role'] !== 'admin' && $this->isLastAdmin($user)) {
            return back()->withErrors(['role' => 'This is the only admin — promote someone else first.']);
        }

        $previous = $user->role;

        $user->forceFill(['role' => $data['role']])->save();
        $user->syncRoles([$data['role']]);

        AuditLog::record('team.role_changed', $request->user(), $user, [
            'member' => $user->email,
            'from' => $previous,
            'to' => $data['role'],
        ]);

        return back()->with('status', "{$user->name} is now a {$data['role']}.");
    }

    private function authoriseSameOrg(Request $request, User $user): void
    {
        abort_unless(
            $user->organization_id === $request->user()->organization_id,
            403,
            'That person is not in your workspace.'
        );
    }

    private function isLastAdmin(User $user): bool
    {
        return User::where('organization_id', $user->organization_id)
            ->where('role', 'admin')
            ->where('id', '!=', $user->id)
            ->doesntExist();
    }
}
