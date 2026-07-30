<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthorized_user_cannot_post_progress_update_to_other_users_task(): void
    {
        $org = Organization::create(['name' => 'Test Org', 'slug' => 'test-org', 'task_seq' => 0, 'onboarding_completed_at' => now()]);

        $owner = User::factory()->create(['organization_id' => $org->id, 'role' => 'employee']);
        $otherEmployee = User::factory()->create(['organization_id' => $org->id, 'role' => 'employee']);

        $task = Task::create([
            'organization_id' => $org->id,
            'title' => 'Private Task',
            'status' => 'to_do',
            'assignee_id' => $owner->id,
            'created_by' => $owner->id,
        ]);

        $response = $this->actingAs($otherEmployee)->post("/tasks/{$task->id}/updates", [
            'body' => 'Unauthorized update attempt',
        ]);

        $response->assertStatus(403);
    }

    public function test_assignee_can_post_progress_update(): void
    {
        $org = Organization::create(['name' => 'Test Org', 'slug' => 'test-org-2', 'task_seq' => 0, 'onboarding_completed_at' => now()]);
        $owner = User::factory()->create(['organization_id' => $org->id, 'role' => 'employee']);

        $task = Task::create([
            'organization_id' => $org->id,
            'title' => 'Assigned Task',
            'status' => 'to_do',
            'assignee_id' => $owner->id,
            'created_by' => $owner->id,
        ]);

        $response = $this->actingAs($owner)->post("/tasks/{$task->id}/updates", [
            'body' => 'Made great progress on this task!',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('task_progress_updates', [
            'task_id' => $task->id,
            'user_id' => $owner->id,
            'body' => 'Made great progress on this task!',
        ]);
    }
}
