<?php

namespace Tests\Feature;

use App\Models\CfocnFilteredRun;
use App\Models\User;
use Tests\TestCase;

/**
 * Covers the Overview page's data source: the latest persisted
 * CfocnFilteredRun (the infrastructure-filtered pipeline's own record),
 * never the old wizard's SavedImport. No RefreshDatabase isolation in this
 * suite, so created rows are cleaned up explicitly.
 */
class CfocnFilteredRunOverviewTest extends TestCase
{
    private array $cleanupRunIds = [];

    private function actingUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    protected function tearDown(): void
    {
        if ($this->cleanupRunIds !== []) {
            CfocnFilteredRun::whereIn('id', $this->cleanupRunIds)->delete();
        }

        parent::tearDown();
    }

    public function test_latest_endpoint_returns_unique_cfocn_counts_not_billing_or_company_side_counts(): void
    {
        $this->actingUser();

        $run = CfocnFilteredRun::create([
            'total_cfocn_work_orders' => 3813,
            'matched' => 2947,
            'not_matched' => 866,
            'matched_plus_not_matched_equals_total' => true,
            'not_match_rows' => [
                ['Work Order' => 'JOB1', 'Infrastructure' => 'CFOCN'],
            ],
            'match_rows' => [
                ['Work Order' => 'JOB2', 'Infrastructure' => 'CFOCN'],
            ],
        ]);
        $this->cleanupRunIds[] = $run->id;

        $response = $this->getJson('/api/cfocn-filter/latest');

        $response->assertOk();
        $response->assertJsonPath('run.summary.total_cfocn_work_orders', 3813);
        $response->assertJsonPath('run.summary.matched', 2947);
        $response->assertJsonPath('run.summary.not_matched', 866);
        $response->assertJsonPath('run.summary.matched_plus_not_matched_equals_total', true);
        $response->assertJsonPath('run.not_match_rows.0.Infrastructure', 'CFOCN');
        $response->assertJsonPath('run.match_rows.0.Work Order', 'JOB2');
        $response->assertJsonMissingPath('run.summary.bill');
        $response->assertJsonMissingPath('run.summary.other_infrastructure');
    }

    public function test_latest_endpoint_defaults_match_rows_to_empty_array_when_column_is_null(): void
    {
        $this->actingUser();

        // Simulates a run row created before match_rows existed.
        $run = CfocnFilteredRun::create([
            'total_cfocn_work_orders' => 5,
            'matched' => 3,
            'not_matched' => 2,
            'matched_plus_not_matched_equals_total' => true,
            'not_match_rows' => [],
            'match_rows' => null,
        ]);
        $this->cleanupRunIds[] = $run->id;

        $response = $this->getJson('/api/cfocn-filter/latest');

        $response->assertOk();
        $response->assertJsonPath('run.match_rows', []);
    }

    public function test_latest_endpoint_returns_the_most_recently_created_run(): void
    {
        $this->actingUser();

        $older = CfocnFilteredRun::create([
            'total_cfocn_work_orders' => 10,
            'matched' => 6,
            'not_matched' => 4,
            'matched_plus_not_matched_equals_total' => true,
            'not_match_rows' => [],
        ]);
        $newer = CfocnFilteredRun::create([
            'total_cfocn_work_orders' => 20,
            'matched' => 12,
            'not_matched' => 8,
            'matched_plus_not_matched_equals_total' => true,
            'not_match_rows' => [],
        ]);
        $this->cleanupRunIds[] = $older->id;
        $this->cleanupRunIds[] = $newer->id;

        $response = $this->getJson('/api/cfocn-filter/latest');

        $response->assertOk();
        $response->assertJsonPath('run.id', $newer->id);
        $response->assertJsonPath('run.summary.total_cfocn_work_orders', 20);
    }

    public function test_latest_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/cfocn-filter/latest');

        $response->assertStatus(401);
    }
}
