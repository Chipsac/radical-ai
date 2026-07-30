<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\TaskProgressUpdate;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

/**
 * Fills a freshly created tenant with a small, realistic sample workspace so
 * a new user has something to click on immediately. Everything created here
 * is ordinary tenant data the user can edit or delete.
 */
class DemoDataSeeder
{
    public function __construct(private TenantContext $tenant) {}

    public function seedFor(Organization $org, User $owner): void
    {
        $this->tenant->asTenant($org->id, function () use ($org, $owner) {
            DB::transaction(function () use ($org, $owner) {
                $categories = TaskCategory::pluck('id', 'name');
                $salesCat = $categories['Sales'] ?? $categories->first();
                $deliveryCat = $categories['Delivery'] ?? $categories->first();

                // ---- CRM ------------------------------------------------
                $accounts = collect([
                    ['Northwind Trading', 'northwind.example', 'Wholesale'],
                    ['Lumen Analytics', 'lumen.example', 'Software'],
                    ['Harbour Logistics', 'harbour.example', 'Transport'],
                ])->map(fn ($a) => Account::create([
                    'organization_id' => $org->id,
                    'name' => $a[0],
                    'website' => 'https://'.$a[1],
                    'industry' => $a[2],
                ]));

                $contacts = $accounts->map(fn ($account, $i) => Contact::create([
                    'organization_id' => $org->id,
                    'first_name' => ['Dana', 'Sam', 'Priya'][$i],
                    'last_name' => ['Whelan', 'Okafor', 'Raman'][$i],
                    'email' => ['dana', 'sam', 'priya'][$i].'@'.parse_url($account->website, PHP_URL_HOST),
                    'title' => ['Operations Lead', 'CTO', 'Head of Procurement'][$i],
                    'account_id' => $account->id,
                    'owner_id' => $owner->id,
                ]));

                foreach ([
                    ['Annual software licence', 0, 24000, 'qualified', 21],
                    ['Analytics pilot project', 1, 12500, 'proposal', 12],
                    ['Fleet optimisation study', 2, 18000, 'lead', 35],
                    ['Support retainer renewal', 0, 9600, 'won', -6],
                ] as $i => [$title, $acct, $value, $stage, $days]) {
                    Deal::create([
                        'organization_id' => $org->id,
                        'title' => $title,
                        'account_id' => $accounts[$acct]->id,
                        'contact_id' => $contacts[$acct]->id,
                        'value' => $value,
                        'stage' => $stage,
                        'owner_id' => $owner->id,
                        'expected_close_date' => now()->addDays($days),
                    ]);
                }

                Lead::create([
                    'organization_id' => $org->id,
                    'name' => 'Chris Bennett',
                    'company_name' => 'Bennett & Co',
                    'email' => 'chris@bennett.example',
                    'source' => 'website',
                    'status' => 'new',
                    'owner_id' => $owner->id,
                ]);

                // ---- Tasks ----------------------------------------------
                foreach ([
                    ['Prepare Q3 sales forecast', 'in_progress', 'high', $salesCat, 4],
                    ['Follow up on analytics pilot', 'to_do', 'medium', $salesCat, 7],
                    ['Onboard Northwind account', 'to_do', 'high', $deliveryCat, 10],
                    ['Write case study draft', 'not_yet_started', 'low', $deliveryCat, 21],
                    ['Renew support contract', 'done', 'medium', $salesCat, -3],
                ] as [$title, $status, $priority, $catId, $due]) {
                    $task = Task::create([
                        'organization_id' => $org->id,
                        'title' => $title,
                        'description' => 'Sample task created during setup — edit or delete it freely.',
                        'status' => $status,
                        'priority' => $priority,
                        'assignee_id' => $owner->id,
                        'category_id' => $catId,
                        'start_date' => now()->subDays(2),
                        'due_date' => now()->addDays($due),
                        'created_by' => $owner->id,
                    ]);

                    if (in_array($status, ['in_progress', 'done'], true)) {
                        TaskProgressUpdate::create([
                            'organization_id' => $org->id,
                            'task_id' => $task->id,
                            'user_id' => $owner->id,
                            'body' => $status === 'done'
                                ? 'Completed and signed off.'
                                : 'Started this — first draft is under way.',
                        ]);
                    }
                }
            });
        });
    }
}
