<?php

namespace Tests\Feature;

use App\Models\Deal;
use App\Models\Organization;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrg(string $slug): Organization
    {
        return Organization::create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'task_seq' => 0,
            'onboarding_completed_at' => now(),
        ]);
    }

    public function test_queries_only_return_the_active_tenants_rows(): void
    {
        $orgA = $this->makeOrg('org-a');
        $orgB = $this->makeOrg('org-b');

        $userA = User::factory()->create(['organization_id' => $orgA->id]);

        Deal::create(['organization_id' => $orgA->id, 'title' => 'A deal', 'value' => 100, 'stage' => 'lead']);
        Deal::create(['organization_id' => $orgB->id, 'title' => 'B deal', 'value' => 200, 'stage' => 'lead']);

        $this->actingAs($userA);

        $titles = Deal::pluck('title');

        $this->assertCount(1, $titles);
        $this->assertSame('A deal', $titles->first());
    }

    /**
     * The critical property: with no tenant context the scope must return
     * nothing rather than everything. A leak here would expose all tenants.
     */
    public function test_scoping_fails_closed_without_a_tenant(): void
    {
        $orgA = $this->makeOrg('org-a');
        Deal::create(['organization_id' => $orgA->id, 'title' => 'A deal', 'value' => 100, 'stage' => 'lead']);

        $this->assertGuest();
        $this->assertCount(0, Deal::all(), 'Unscoped access must not return other tenants data.');
    }

    public function test_cross_tenant_writes_are_blocked(): void
    {
        $orgA = $this->makeOrg('org-a');
        $orgB = $this->makeOrg('org-b');

        $userA = User::factory()->create(['organization_id' => $orgA->id]);
        $this->actingAs($userA);

        $this->expectException(\RuntimeException::class);

        // Explicitly trying to write into another tenant must be refused.
        Deal::create([
            'organization_id' => $orgB->id,
            'title' => 'Smuggled deal',
            'value' => 999,
            'stage' => 'lead',
        ]);
    }

    public function test_new_records_are_stamped_with_the_active_tenant(): void
    {
        $orgA = $this->makeOrg('org-a');
        $userA = User::factory()->create(['organization_id' => $orgA->id]);

        $this->actingAs($userA);

        $deal = Deal::create(['title' => 'Auto stamped', 'value' => 10, 'stage' => 'lead']);

        $this->assertSame($orgA->id, $deal->organization_id);
    }

    public function test_explicit_tenant_context_works_without_a_logged_in_user(): void
    {
        $orgA = $this->makeOrg('org-a');
        Deal::create(['organization_id' => $orgA->id, 'title' => 'A deal', 'value' => 100, 'stage' => 'lead']);

        $found = app(TenantContext::class)->asTenant($orgA->id, fn () => Deal::count());

        $this->assertSame(1, $found);
    }

    public function test_cross_tenant_access_is_refused_over_http(): void
    {
        $orgA = $this->makeOrg('org-a');
        $orgB = $this->makeOrg('org-b');

        $userA = User::factory()->create(['organization_id' => $orgA->id]);
        $dealB = Deal::create(['organization_id' => $orgB->id, 'title' => 'B deal', 'value' => 200, 'stage' => 'lead']);

        $response = $this->actingAs($userA)->get("/crm/deals/{$dealB->id}");

        $this->assertContains($response->status(), [403, 404], 'Another tenant\'s deal must not be reachable.');
    }
}
