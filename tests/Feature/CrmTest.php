<?php

namespace Tests\Feature;

use App\Models\Deal;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_and_update_deal_stage(): void
    {
        $org = Organization::create(['name' => 'Test Org', 'slug' => 'test-org', 'task_seq' => 0]);
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'admin']);

        $response = $this->actingAs($user)->post('/crm/deals', [
            'title' => 'Acme Enterprise License',
            'value' => 50000,
            'stage' => 'qualified',
        ]);

        $response->assertRedirect('/crm/deals');

        $deal = Deal::where('title', 'Acme Enterprise License')->first();
        $this->assertNotNull($deal);
        $this->assertEquals('qualified', $deal->stage);

        $patchResponse = $this->actingAs($user)->patchJson("/crm/deals/{$deal->id}/stage", [
            'stage' => 'proposal',
        ]);

        $patchResponse->assertOk();
        $this->assertEquals('proposal', $deal->fresh()->stage);
    }

    public function test_user_cannot_access_or_modify_other_organization_deal(): void
    {
        $org1 = Organization::create(['name' => 'Org 1', 'slug' => 'org-1', 'task_seq' => 0]);
        $org2 = Organization::create(['name' => 'Org 2', 'slug' => 'org-2', 'task_seq' => 0]);

        $user1 = User::factory()->create(['organization_id' => $org1->id]);
        $user2 = User::factory()->create(['organization_id' => $org2->id]);

        $deal = Deal::create([
            'organization_id' => $org1->id,
            'title' => 'Secret Deal Org 1',
            'value' => 10000,
            'stage' => 'lead',
            'owner_id' => $user1->id,
        ]);

        $response = $this->actingAs($user2)->get("/crm/deals/{$deal->id}");
        $response->assertStatus(404);
    }
}
