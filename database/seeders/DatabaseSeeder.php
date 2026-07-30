<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Lead;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\Payslip;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\TaskProgressUpdate;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Roles -------------------------------------------------------
        foreach (['admin', 'manager', 'employee'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // ---- Organization ------------------------------------------------
        $org = Organization::create([
            'name' => 'Acme Startup Ltd',
            'slug' => 'acme-startup',
            'plan_tier' => 'startup',
            'onboarding_step' => 'done',
            'onboarding_completed_at' => now(),
        ]);

        // Tenant scoping fails closed, so the seeder must declare which tenant
        // it is writing as — there is no authenticated user here.
        app(\App\Support\TenantContext::class)->set($org->id);

        // ---- Users + employees -------------------------------------------
        $mkUser = function (string $name, string $email, string $role, string $title, string $dept, ?int $managerId = null) use ($org) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('password'),
                'organization_id' => $org->id,
                'role' => $role,
                'email_verified_at' => now(),
            ]);
            $user->assignRole($role);

            Employee::create([
                'organization_id' => $org->id,
                'user_id' => $user->id,
                'department' => $dept,
                'job_title' => $title,
                'manager_id' => $managerId,
                'start_date' => now()->subMonths(rand(4, 40))->startOfMonth(),
                'status' => 'active',
            ]);

            return $user;
        };

        $admin = $mkUser('Aoife Kelly', 'admin@acme.test', 'admin', 'Founder & CEO', 'Leadership');
        $manager = $mkUser('Marcus Doyle', 'manager@acme.test', 'manager', 'Programme Manager', 'Operations', $admin->id);
        $employee = $mkUser('Sinead Kavanagh', 'employee@acme.test', 'employee', 'Business Lead', 'Business Development', $manager->id);

        $teamSpec = [
            ['Orla Brennan', 'Operations Director', 'Operations', 'manager'],
            ['Tomás Ryan', 'Graphic Designer', 'Communications', 'employee'],
            ['Niamh Fitzgerald', 'Grant Writer', 'Funding', 'employee'],
            ['Cian Murphy', 'IT Systems Lead', 'IT', 'employee'],
            ['Ella Nolan', 'Communications Officer', 'Communications', 'employee'],
            ['Patrick Walsh', 'Finance Officer', 'Finance', 'employee'],
            ['Grace O\'Connor', 'Events Coordinator', 'Events', 'employee'],
            ['Liam Hughes', 'Research Analyst', 'Research', 'employee'],
            ['Sofia Marino', 'Partnerships Lead', 'Business Development', 'employee'],
            ['David Chen', 'Software Developer', 'IT', 'employee'],
            ['Emma Byrne', 'HR Generalist', 'People', 'employee'],
            ['Jack Doherty', 'Catering Supervisor', 'Events', 'employee'],
            ['Aisling Moran', 'Advisory Board Liaison', 'Leadership', 'employee'],
            ['Ryan Kennedy', 'Junior Designer', 'Communications', 'employee'],
        ];

        $team = [$admin, $manager, $employee];
        foreach ($teamSpec as [$name, $title, $dept, $role]) {
            $email = strtolower(preg_replace('/[^a-z]+/i', '.', $name)).'@acme.test';
            $email = trim(preg_replace('/\.+/', '.', $email), '.');
            $email = str_replace('.@', '@', $email);
            $team[] = $mkUser($name, $email, $role, $title, $dept, $manager->id);
        }

        // ---- Departments -------------------------------------------------
        foreach ([
            ['Leadership', $admin->id],
            ['Operations', $manager->id],
            ['Communications', $team[4]->id],
            ['IT', $team[6]->id],
            ['Funding', $team[5]->id],
            ['Business Development', $employee->id],
            ['Events', $team[9]->id],
            ['Finance', $team[8]->id],
        ] as [$name, $leadId]) {
            Department::create(['organization_id' => $org->id, 'name' => $name, 'lead_id' => $leadId]);
        }

        // ---- Task categories ---------------------------------------------
        $catSpec = [
            ['Advisory Boards', '#A855F7', 'AB'],
            ['Catering', '#EF7C4A', 'CAT'],
            ['Communications', '#3B82F6', 'COM'],
            ['Spokes Projects', '#22C55E', 'SP'],
            ['IT', '#06B6D4', 'IT'],
            ['Funding & Grants', '#E0A44E', 'FG'],
            ['Events', '#EC4899', 'EV'],
            ['Operations', '#8A8A8A', 'OPS'],
        ];
        $cats = [];
        foreach ($catSpec as [$name, $color, $prefix]) {
            $cats[$name] = TaskCategory::create([
                'organization_id' => $org->id,
                'name' => $name,
                'color' => $color,
                'prefix' => $prefix,
            ]);
        }

        // ---- Tasks -------------------------------------------------------
        $taskSpec = [
            // [title, status, priority, category, daysToDue, updates]
            ['Draft Q3 advisory board agenda', 'to_do', 'high', 'Advisory Boards', 5, 2],
            ['Book advisory board venue', 'in_progress', 'medium', 'Advisory Boards', 8, 3],
            ['Confirm catering numbers for summit', 'to_do', 'medium', 'Catering', 3, 1],
            ['Negotiate new catering contract', 'in_progress', 'high', 'Catering', 12, 2],
            ['Publish July newsletter', 'review', 'high', 'Communications', -2, 4],
            ['Refresh website hero banner', 'to_do', 'low', 'Communications', 15, 1],
            ['Schedule social posts for launch week', 'not_yet_started', 'medium', 'Communications', 10, 1],
            ['Design annual report layout', 'in_progress', 'medium', 'Communications', 20, 3],
            ['Onboard Ballina spoke project', 'in_progress', 'high', 'Spokes Projects', 6, 3],
            ['Collect spoke project KPIs', 'to_do', 'medium', 'Spokes Projects', -4, 2],
            ['Review spoke funding applications', 'review', 'high', 'Spokes Projects', 2, 2],
            ['Migrate shared drive to new tenant', 'in_progress', 'high', 'IT', 9, 4],
            ['Roll out password manager', 'not_yet_started', 'medium', 'IT', 18, 1],
            ['Fix printer VLAN issue', 'done', 'low', 'IT', -6, 2],
            ['Submit Enterprise Ireland grant', 'to_do', 'high', 'Funding & Grants', 4, 3],
            ['Draft philanthropy prospect list', 'not_yet_started', 'low', 'Funding & Grants', 25, 1],
            ['Reconcile grant expenditure Q2', 'done', 'medium', 'Funding & Grants', -10, 3],
            ['Plan autumn showcase event', 'to_do', 'medium', 'Events', 30, 2],
            ['Debrief spring conference', 'done', 'low', 'Events', -15, 2],
            ['Update supplier onboarding SOP', 'review', 'medium', 'Operations', 7, 2],
            ['Quarterly risk register review', 'not_yet_started', 'medium', 'Operations', 14, 1],
            ['Renew office insurance', 'done', 'high', 'Operations', -20, 1],
        ];

        $updatesPool = [
            'Kicked this off today — gathering the background docs.',
            'Waiting on a reply from the external contact before I can progress.',
            'About 50% through, on track for the due date.',
            'Blocked briefly by approvals; escalated to the manager.',
            'Draft shared with the team for comments.',
            'Feedback incorporated, moving to final version.',
            'Completed and circulated to stakeholders.',
            'Scope tweaked after this week\'s stand-up.',
        ];

        foreach ($taskSpec as $i => [$title, $status, $priority, $cat, $dueOffset, $nUpdates]) {
            $assignee = $team[$i % count($team)];
            $task = Task::create([
                'organization_id' => $org->id,
                'title' => $title,
                'description' => "Seeded demo task: {$title}. Full context lives in the team drive; use the progress thread below for updates.",
                'status' => $status,
                'priority' => $priority,
                'assignee_id' => $assignee->id,
                'category_id' => $cats[$cat]->id,
                'start_date' => now()->subDays(rand(3, 20)),
                'due_date' => now()->addDays($dueOffset),
                'is_continuous' => $i % 9 === 0,
                'created_by' => $manager->id,
            ]);

            for ($u = 0; $u < $nUpdates; $u++) {
                TaskProgressUpdate::create([
                    'organization_id' => $org->id,
                    'task_id' => $task->id,
                    'user_id' => $assignee->id,
                    'body' => $updatesPool[($i + $u) % count($updatesPool)],
                    'created_at' => now()->subDays($nUpdates - $u),
                    'updated_at' => now()->subDays($nUpdates - $u),
                ]);
            }

            // Logged hours feed the utilisation figures on HR profiles
            if ($i % 2 === 0) {
                foreach (range(1, rand(2, 4)) as $d) {
                    TimeEntry::create([
                        'organization_id' => $org->id,
                        'task_id' => $task->id,
                        'user_id' => $assignee->id,
                        'minutes' => rand(30, 240),
                        'entry_date' => now()->subDays($d)->toDateString(),
                    ]);
                }
            }
        }

        // ---- CRM ---------------------------------------------------------
        $accountSpec = [
            ['Galway Bay Foods', 'galwaybayfoods.ie', 'Food & Beverage'],
            ['Atlantic MedTech', 'atlanticmedtech.com', 'Medical Devices'],
            ['Corrib Renewables', 'corribrenewables.ie', 'Energy'],
            ['Shannon Logistics', 'shannonlogistics.ie', 'Transport'],
            ['Burren Analytics', 'burrenanalytics.io', 'Software'],
            ['Claddagh Retail Group', 'claddaghretail.ie', 'Retail'],
        ];
        $accounts = [];
        foreach ($accountSpec as [$name, $site, $industry]) {
            $accounts[] = Account::create([
                'organization_id' => $org->id,
                'name' => $name,
                'website' => 'https://'.$site,
                'industry' => $industry,
            ]);
        }

        $contactSpec = [
            ['Mary', 'Lydon', 'Procurement Lead'],
            ['Sean', 'Flaherty', 'Managing Director'],
            ['Katie', 'Burke', 'Head of Innovation'],
            ['Owen', 'Duffy', 'Operations Manager'],
            ['Laura', 'Griffin', 'CTO'],
            ['Brian', 'Connolly', 'Commercial Director'],
        ];
        $contacts = [];
        foreach ($contactSpec as $i => [$fn, $ln, $title]) {
            $contacts[] = Contact::create([
                'organization_id' => $org->id,
                'first_name' => $fn,
                'last_name' => $ln,
                'email' => strtolower($fn.'.'.$ln).'@'.parse_url($accounts[$i]->website, PHP_URL_HOST),
                'phone' => '+353 91 '.rand(100000, 999999),
                'title' => $title,
                'account_id' => $accounts[$i]->id,
                'owner_id' => $team[$i % 3]->id,
            ]);
        }

        $dealSpec = [
            ['Website revamp retainer', 0, 18500, 'lead', 30],
            ['Festival sponsorship package', 1, 9000, 'lead', 21],
            ['Analytics dashboard pilot', 4, 27000, 'qualified', 45],
            ['Logistics automation study', 3, 15500, 'qualified', 25],
            ['Retail loyalty programme', 5, 32000, 'qualified', 60],
            ['MedTech training programme', 1, 22000, 'proposal', 14],
            ['Renewables comms campaign', 2, 12800, 'proposal', 10],
            ['Supply chain audit', 3, 8400, 'proposal', 7],
            ['Innovation workshop series', 2, 16750, 'won', -5],
            ['Brand refresh project', 0, 21000, 'won', -12],
            ['Data migration engagement', 4, 11000, 'lost', -8],
            ['Pop-up events programme', 5, 6900, 'lost', -20],
        ];
        foreach ($dealSpec as $i => [$title, $acctIdx, $value, $stage, $closeOffset]) {
            Deal::create([
                'organization_id' => $org->id,
                'title' => $title,
                'account_id' => $accounts[$acctIdx]->id,
                'contact_id' => $contacts[$acctIdx]->id,
                'value' => $value,
                'stage' => $stage,
                'owner_id' => $team[$i % 3]->id,
                'expected_close_date' => now()->addDays($closeOffset),
            ]);
        }

        foreach ([
            ['Deirdre Halloran', 'Westport Craft Collective', 'website'],
            ['Paul Nestor', 'Nestor Engineering', 'referral'],
            ['Yuki Tanaka', 'Tanaka Trading', 'conference'],
            ['Aoibhinn Clarke', 'Clarke & Daughters', 'linkedin'],
            ['Mikhail Petrov', 'Baltic Imports', 'website'],
        ] as $i => [$name, $company, $source]) {
            Lead::create([
                'organization_id' => $org->id,
                'name' => $name,
                'company_name' => $company,
                'email' => strtolower(str_replace(' ', '.', $name)).'@example.com',
                'source' => $source,
                'status' => ['new', 'contacted', 'new', 'qualified', 'new'][$i],
                'owner_id' => $team[$i % 3]->id,
            ]);
        }

        // ---- Leave -------------------------------------------------------
        $leaveTypes = [];
        foreach ([
            ['Annual Leave', true, 25],
            ['Sick Leave', true, 10],
            ['Unpaid Leave', false, 0],
        ] as [$name, $paid, $days]) {
            $leaveTypes[$name] = LeaveType::create([
                'organization_id' => $org->id,
                'name' => $name,
                'paid' => $paid,
                'default_allowance_days' => $days,
            ]);
        }

        foreach (Employee::withoutGlobalScopes()->get() as $emp) {
            foreach ($leaveTypes as $lt) {
                LeaveBalance::create([
                    'organization_id' => $org->id,
                    'employee_id' => $emp->id,
                    'leave_type_id' => $lt->id,
                    'year' => now()->year,
                    'entitlement_days' => $lt->default_allowance_days,
                    'taken_days' => $lt->name === 'Annual Leave' ? rand(0, 8) : 0,
                ]);
            }
        }

        $empRecord = fn (User $u) => Employee::withoutGlobalScopes()->where('user_id', $u->id)->first();

        $leaveSpec = [
            // [user, type, startOffset, length, status]
            [$employee, 'Annual Leave', 12, 5, 'pending'],
            [$team[7], 'Annual Leave', -1, 4, 'approved'],   // on leave right now
            [$team[10], 'Sick Leave', -3, 2, 'approved'],
            [$team[12], 'Annual Leave', 20, 10, 'pending'],
        ];
        foreach ($leaveSpec as [$user, $type, $startOffset, $len, $status]) {
            LeaveRequest::create([
                'organization_id' => $org->id,
                'employee_id' => $empRecord($user)->id,
                'leave_type_id' => $leaveTypes[$type]->id,
                'start_date' => now()->addDays($startOffset)->toDateString(),
                'end_date' => now()->addDays($startOffset + $len - 1)->toDateString(),
                'days' => $len,
                'reason' => $type === 'Sick Leave' ? 'Under the weather.' : 'Family holiday.',
                'status' => $status,
                'approver_id' => $status === 'approved' ? $manager->id : null,
                'decided_at' => $status === 'approved' ? now()->subDays(2) : null,
            ]);
        }

        // ---- Payslips (placeholder PDFs) ---------------------------------
        File::ensureDirectoryExists(storage_path('app/payslips'));

        $empForPayslips = $empRecord($employee);
        foreach ([
            ['April 2026', '2026-04-28', 4200.00, 3187.55],
            ['May 2026', '2026-05-28', 4200.00, 3187.55],
            ['June 2026', '2026-06-26', 4350.00, 3282.10],
            ['July 2026', '2026-07-28', 4350.00, 3282.10],
        ] as [$period, $date, $gross, $net]) {
            $file = 'payslips/payslip-'.str_replace(' ', '-', strtolower($period)).'.pdf';
            $this->writePlaceholderPdf(storage_path('app/'.$file), [
                'ACME STARTUP LTD — PAYSLIP',
                'Employee: '.$employee->name,
                'Period: '.$period,
                'Pay date: '.$date,
                sprintf('Gross pay: EUR %0.2f', $gross),
                sprintf('Net pay: EUR %0.2f', $net),
                'This is a seeded demo payslip (no tax calculation).',
            ]);

            Payslip::create([
                'organization_id' => $org->id,
                'employee_id' => $empForPayslips->id,
                'period_label' => $period,
                'pay_date' => $date,
                'gross' => $gross,
                'net' => $net,
                'file_path' => $file,
                'uploaded_by' => $admin->id,
            ]);
        }

        $org->update(['owner_id' => $admin->id]);

        app(\App\Support\TenantContext::class)->forget();
    }

    /**
     * Write a minimal valid one-page PDF containing the given text lines.
     */
    private function writePlaceholderPdf(string $path, array $lines): void
    {
        $content = "BT /F1 14 Tf 50 780 Td 20 TL\n";
        foreach ($lines as $line) {
            $safe = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
            $content .= "({$safe}) Tj T*\n";
        }
        $content .= 'ET';

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Length '.strlen($content)." >>\nstream\n{$content}\nendstream",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n{$obj}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $off) {
            $pdf .= sprintf("%010d 00000 n \n", $off);
        }
        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        file_put_contents($path, $pdf);
    }
}
