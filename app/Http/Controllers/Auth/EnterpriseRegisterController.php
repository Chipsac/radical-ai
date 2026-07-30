<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\TaskCategory;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class EnterpriseRegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register-enterprise');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'company_name' => ['required', 'string', 'max:255'],
            'company_slug' => ['nullable', 'string', 'max:100', 'alpha_dash'],
            'plan_tier' => ['required', 'in:free_trial,starter,pro,enterprise'],
            'size_range' => ['nullable', 'string', 'max:50'],
            'industry' => ['nullable', 'string', 'max:100'],
        ]);

        $user = DB::transaction(function () use ($request) {
            $slugBase = $request->filled('company_slug')
                ? Str::slug($request->company_slug)
                : Str::slug($request->company_name);

            $slug = $slugBase;
            $count = 1;
            while (Organization::where('slug', $slug)->exists()) {
                $slug = $slugBase.'-'.$count++;
            }

            $organization = Organization::create([
                'name' => $request->company_name,
                'slug' => $slug,
                'plan_tier' => $request->plan_tier,
                'industry' => $request->industry ?? 'Technology',
                'size_range' => $request->size_range ?? '1-10',
                'billing_status' => 'active',
                'trial_ends_at' => now()->addDays(14),
                'task_seq' => 0,
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'organization_id' => $organization->id,
                'role' => 'admin',
                'onboarding_step' => 1,
            ]);

            $adminRole = Role::firstOrCreate(['name' => 'admin']);
            Role::firstOrCreate(['name' => 'manager']);
            Role::firstOrCreate(['name' => 'employee']);
            $user->assignRole($adminRole);

            Employee::create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'job_title' => 'Founder / Administrator',
                'department' => 'Management',
                'status' => 'active',
                'start_date' => now()->toDateString(),
            ]);

            $categories = [
                ['name' => 'Operations', 'color' => '#3B82F6', 'prefix' => 'OPS'],
                ['name' => 'IT & Systems', 'color' => '#A855F7', 'prefix' => 'IT'],
                ['name' => 'Sales & CRM', 'color' => '#22C55E', 'prefix' => 'CRM'],
                ['name' => 'Human Resources', 'color' => '#EF4444', 'prefix' => 'HR'],
            ];

            foreach ($categories as $cat) {
                TaskCategory::create($cat + ['organization_id' => $organization->id]);
            }

            $leaveTypes = [
                ['name' => 'Annual Leave', 'paid' => true],
                ['name' => 'Sick Leave', 'paid' => true],
                ['name' => 'Unpaid Leave', 'paid' => false],
            ];

            foreach ($leaveTypes as $lt) {
                LeaveType::create($lt + ['organization_id' => $organization->id]);
            }

            return $user;
        });

        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('onboarding.index');
    }
}
