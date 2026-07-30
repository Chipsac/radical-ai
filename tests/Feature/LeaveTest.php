<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeaveTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_approval_increments_leave_balance(): void
    {
        $org = Organization::create(['name' => 'Test Org', 'slug' => 'test-org', 'task_seq' => 0]);
        $role = Role::create(['name' => 'manager']);

        $manager = User::factory()->create(['organization_id' => $org->id, 'role' => 'manager']);
        $manager->assignRole($role);

        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'employee']);

        $employee = Employee::create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'job_title' => 'Engineer',
            'department' => 'Engineering',
            'status' => 'active',
        ]);

        $leaveType = LeaveType::create([
            'organization_id' => $org->id,
            'name' => 'Annual Leave',
            'paid' => true,
        ]);

        $leaveRequest = LeaveRequest::create([
            'organization_id' => $org->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-05',
            'days' => 3,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($manager)->patch("/hr/leave/{$leaveRequest->id}/decide", [
            'decision' => 'approved',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals('approved', $leaveRequest->fresh()->status);

        $this->assertDatabaseHas('leave_balances', [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'taken_days' => 3,
        ]);
    }
}
