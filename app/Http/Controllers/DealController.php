<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DealController extends Controller
{
    public function index(Request $request)
    {
        $deals = Deal::with(['account', 'contact', 'owner'])
            ->orderByDesc('value')
            ->get()
            ->groupBy('stage');

        return view('crm.deals.index', [
            'columns' => collect(Deal::STAGES)->mapWithKeys(fn ($s) => [$s => $deals->get($s, collect())]),
            'accounts' => Account::orderBy('name')->get(),
            'contacts' => Contact::orderBy('first_name')->get(),
            'owners' => User::where('organization_id', $request->user()->organization_id)->orderBy('name')->get(),
        ]);
    }

    public function show(Deal $deal)
    {
        $this->authorizeDeal($deal);
        $deal->load(['account', 'contact', 'owner', 'project.tasks.assignee']);

        return view('crm.deals.show', ['deal' => $deal]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'account_id' => ['nullable', 'exists:accounts,id'],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'value' => ['required', 'numeric', 'min:0'],
            'stage' => ['nullable', 'in:'.implode(',', Deal::STAGES)],
            'expected_close_date' => ['nullable', 'date'],
        ]);

        Deal::create($data + [
            'stage' => $data['stage'] ?? 'lead',
            'owner_id' => $request->user()->id,
        ]);

        return redirect()->route('crm.deals.index')->with('status', 'Deal created.');
    }

    public function updateStage(Request $request, Deal $deal)
    {
        $this->authorizeDeal($deal);

        $data = $request->validate([
            'stage' => ['required', 'in:'.implode(',', Deal::STAGES)],
        ]);

        $deal->update(['stage' => $data['stage']]);

        return response()->json(['ok' => true, 'id' => $deal->id, 'stage' => $deal->stage]);
    }

    /**
     * Cross-module glue: winning a deal creates a project plus onboarding
     * tasks assigned to employees who are not on approved leave today.
     */
    public function markWon(Request $request, Deal $deal)
    {
        $this->authorizeDeal($deal);

        if ($deal->project_id) {
            return redirect()->route('crm.deals.show', $deal)
                ->with('status', 'Deal already won — project exists.');
        }

        DB::transaction(function () use ($deal, $request) {
            $deal->update(['stage' => 'won']);

            $project = Project::create([
                'name' => $deal->title,
                'description' => 'Delivery project created automatically when the deal was marked won.',
                'status' => 'active',
                'owner_id' => $deal->owner_id ?? $request->user()->id,
                'source_deal_id' => $deal->id,
            ]);

            $deal->update(['project_id' => $project->id]);

            $available = Employee::with('user')
                ->where('status', 'active')
                ->get()
                ->filter(fn ($e) => ! $e->isOnLeave())
                ->pluck('user')
                ->filter()
                ->values();

            $opsCategory = TaskCategory::where('name', 'Operations')->first();

            $onboarding = [
                ['Kick-off call with '.($deal->account->name ?? 'client'), 'high', 3],
                ['Draft statement of work', 'high', 7],
                ['Set up project workspace & billing codes', 'medium', 5],
            ];

            foreach ($onboarding as $i => [$title, $priority, $dueDays]) {
                $assignee = $available->isNotEmpty() ? $available[$i % $available->count()] : null;
                Task::create([
                    'title' => $title,
                    'description' => "Onboarding task auto-created from won deal “{$deal->title}”.",
                    'status' => 'to_do',
                    'priority' => $priority,
                    'assignee_id' => $assignee?->id,
                    'category_id' => $opsCategory?->id,
                    'project_id' => $project->id,
                    'start_date' => now(),
                    'due_date' => now()->addDays($dueDays),
                    'created_by' => $request->user()->id,
                ]);
            }
        });

        return redirect()->route('crm.deals.show', $deal)
            ->with('status', 'Deal won! Project and onboarding tasks created.');
    }

    private function authorizeDeal(Deal $deal): void
    {
        $user = auth()->user();
        abort_unless(
            $user && ($deal->organization_id === $user->organization_id),
            403,
            'Unauthorized access to deal.'
        );
    }
}
