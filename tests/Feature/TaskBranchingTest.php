<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaskBranchingTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'manager', 'employee'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->org = Organization::create([
            'name' => 'Branch Co', 'slug' => 'branch-co', 'task_seq' => 0,
            'onboarding_completed_at' => now(),
        ]);

        $this->manager = User::factory()->create([
            'organization_id' => $this->org->id,
            'role' => 'manager',
            'email_verified_at' => now(),
        ]);
        $this->manager->assignRole('manager');
    }

    private function task(string $title, ?Task $parent = null, string $status = 'to_do'): Task
    {
        return Task::create([
            'organization_id' => $this->org->id,
            'title' => $title,
            'status' => $status,
            'priority' => 'medium',
            'parent_id' => $parent?->id,
            'assignee_id' => $this->manager->id,
            'created_by' => $this->manager->id,
        ]);
    }

    // ---- Structure -------------------------------------------------------

    public function test_a_task_can_have_many_subtasks(): void
    {
        $this->actingAs($this->manager);

        $parent = $this->task('Onboard new client');
        $this->task('Kick-off call', $parent);
        $this->task('Draft statement of work', $parent);
        $this->task('Set up billing', $parent);

        $this->assertCount(3, $parent->fresh()->children);
        $this->assertTrue($parent->fresh()->hasChildren());
    }

    public function test_nesting_is_unbounded(): void
    {
        $this->actingAs($this->manager);

        // Five levels deep, which the UI caps visually but the model allows.
        $node = $this->task('Level 0');
        $chain = [$node];

        for ($i = 1; $i <= 5; $i++) {
            $node = $this->task("Level {$i}", $node);
            $chain[] = $node;
        }

        $this->assertSame(5, $node->fresh()->depth);
        $this->assertCount(5, $node->fresh()->ancestors());
        $this->assertSame($chain[0]->id, $node->fresh()->rootTask()->id);
    }

    public function test_depth_is_recorded_as_the_tree_is_built(): void
    {
        $this->actingAs($this->manager);

        $root = $this->task('Root');
        $child = $this->task('Child', $root);
        $grandchild = $this->task('Grandchild', $child);

        $this->assertSame(0, $root->fresh()->depth);
        $this->assertSame(1, $child->fresh()->depth);
        $this->assertSame(2, $grandchild->fresh()->depth);
    }

    public function test_descendants_returns_the_whole_branch(): void
    {
        $this->actingAs($this->manager);

        $root = $this->task('Root');
        $a = $this->task('A', $root);
        $this->task('A1', $a);
        $this->task('A2', $a);
        $this->task('B', $root);

        $this->assertCount(4, $root->fresh()->descendants());
    }

    // ---- The critical guard ---------------------------------------------

    /**
     * With unbounded nesting a cycle would send every recursive walk —
     * progress, depth, rendering — into an infinite loop.
     */
    public function test_a_task_cannot_become_its_own_parent(): void
    {
        $this->actingAs($this->manager);

        $task = $this->task('Self');

        $this->expectException(\RuntimeException::class);

        $task->parent_id = $task->id;
        $task->save();
    }

    public function test_a_task_cannot_be_moved_inside_its_own_branch(): void
    {
        $this->actingAs($this->manager);

        $root = $this->task('Root');
        $child = $this->task('Child', $root);
        $grandchild = $this->task('Grandchild', $child);

        $this->expectException(\RuntimeException::class);

        // Root under its own grandchild would close the loop.
        $root->parent_id = $grandchild->id;
        $root->save();
    }

    public function test_the_move_endpoint_reports_a_cycle_instead_of_erroring(): void
    {
        $this->actingAs($this->manager);

        $root = $this->task('Root');
        $child = $this->task('Child', $root);

        $this->patch(route('tasks.move', $root), ['parent_id' => $child->id])
            ->assertSessionHasErrors('parent_id');

        $this->assertNull($root->fresh()->parent_id);
    }

    // ---- Moving ----------------------------------------------------------

    public function test_moving_a_branch_restamps_descendant_depths(): void
    {
        $this->actingAs($this->manager);

        $rootA = $this->task('Root A');
        $rootB = $this->task('Root B');
        $child = $this->task('Child', $rootA);
        $grandchild = $this->task('Grandchild', $child);

        $this->assertSame(2, $grandchild->fresh()->depth);

        // Move the middle of the branch one level deeper.
        $child->parent_id = $rootB->id;
        $child->save();

        $this->assertSame(1, $child->fresh()->depth);
        $this->assertSame(2, $grandchild->fresh()->depth, 'Descendants must follow their parent.');
    }

    public function test_a_subtask_can_be_promoted_to_top_level(): void
    {
        $this->actingAs($this->manager);

        $root = $this->task('Root');
        $child = $this->task('Child', $root);

        $this->patch(route('tasks.move', $child), ['parent_id' => ''])
            ->assertSessionHasNoErrors();

        $child->refresh();
        $this->assertNull($child->parent_id);
        $this->assertSame(0, $child->depth);
    }

    // ---- Progress --------------------------------------------------------

    public function test_branch_progress_reflects_the_whole_branch(): void
    {
        $this->actingAs($this->manager);

        $root = $this->task('Root');
        $this->task('Done one', $root, 'done');
        $this->task('Done two', $root, 'done');
        $this->task('Still going', $root);

        // 2 of 4 (three children plus the unfinished parent).
        $this->assertSame(50, $root->fresh()->branchProgress());
    }

    public function test_a_leaf_task_is_simply_done_or_not(): void
    {
        $this->actingAs($this->manager);

        $open = $this->task('Open');
        $done = $this->task('Finished', null, 'done');

        $this->assertSame(0, $open->branchProgress());
        $this->assertSame(100, $done->branchProgress());
    }

    // ---- HTTP ------------------------------------------------------------

    public function test_a_subtask_can_be_added_from_the_task_page(): void
    {
        $this->actingAs($this->manager);
        $parent = $this->task('Parent');

        $this->post(route('tasks.subtasks.store', $parent), [
            'title' => 'A concrete step',
            'priority' => 'high',
        ])->assertSessionHasNoErrors();

        $child = $parent->fresh()->children->first();
        $this->assertSame('A concrete step', $child->title);
        $this->assertSame($parent->category_id, $child->category_id, 'Subtasks inherit their parent category.');
    }

    public function test_the_board_shows_top_level_work_by_default(): void
    {
        $this->actingAs($this->manager);

        $parent = $this->task('Top level item');
        $this->task('Hidden subtask', $parent);

        $response = $this->get('/tasks/board');

        $response->assertSee('Top level item');
        $response->assertDontSee('Hidden subtask');
    }

    public function test_the_board_can_show_subtasks_when_asked(): void
    {
        $this->actingAs($this->manager);

        $parent = $this->task('Top level item');
        $this->task('Nested item', $parent);

        $this->get('/tasks/board?show_subtasks=1')->assertSee('Nested item');
    }
}
