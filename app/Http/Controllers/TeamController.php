<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $teams = Team::with(['employees.user', 'lead'])->orderBy('name')->get();

        // Today's coverage per team, so a manager sees at a glance who is thin.
        $coverage = $teams->mapWithKeys(fn (Team $t) => [$t->id => $t->coverageOn(now())]);

        return view('hr.teams', [
            'teams' => $teams,
            'coverage' => $coverage,
            'unassigned' => Employee::with('user')->whereNull('team_id')->get(),
            'candidates' => User::where('organization_id', $request->user()->organization_id)
                ->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
            'lead_id' => ['nullable', 'exists:users,id'],
            'minimum_cover' => ['required', 'integer', 'min:0', 'max:50'],
        ]);

        $team = Team::create($data + ['slug' => $this->uniqueSlug($data['name'])]);

        AuditLog::record('team.created', $request->user(), $team, ['name' => $team->name]);

        return back()->with('status', "Team “{$team->name}” created.");
    }

    public function update(Request $request, Team $team)
    {
        $this->authoriseSameOrg($request, $team);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
            'lead_id' => ['nullable', 'exists:users,id'],
            'minimum_cover' => ['required', 'integer', 'min:0', 'max:50'],
        ]);

        $team->update($data);

        return back()->with('status', "Team “{$team->name}” updated.");
    }

    public function destroy(Request $request, Team $team)
    {
        $this->authoriseSameOrg($request, $team);

        // Members are released rather than deleted with the team.
        Employee::where('team_id', $team->id)->update(['team_id' => null]);
        $name = $team->name;
        $team->delete();

        AuditLog::record('team.deleted', $request->user(), null, ['name' => $name]);

        return back()->with('status', "Team “{$name}” removed. Its members are now unassigned.");
    }

    /** Move someone into a team, or out of all teams with a blank value. */
    public function assign(Request $request, Employee $employee)
    {
        abort_unless(
            $employee->organization_id === $request->user()->organization_id,
            403,
            'That person is not in your workspace.'
        );

        $data = $request->validate([
            'team_id' => ['nullable', 'exists:teams,id'],
        ]);

        $employee->update([
            'team_id' => $data['team_id'] ?: null,
            // Keep the legacy label in step so the directory stays coherent.
            'department' => $data['team_id']
                ? Team::find($data['team_id'])?->name
                : $employee->department,
        ]);

        return back()->with('status', $data['team_id']
            ? "{$employee->user?->name} moved to ".Team::find($data['team_id'])?->name.'.'
            : "{$employee->user?->name} removed from their team.");
    }

    private function authoriseSameOrg(Request $request, Team $team): void
    {
        abort_unless(
            $team->organization_id === $request->user()->organization_id,
            403,
            'That team is not in your workspace.'
        );
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'team';
        $slug = $base;
        $i = 1;

        while (Team::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
