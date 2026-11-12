<?php

namespace Tests\Feature;

use App\Models\VerifiedInfrastructure;
use App\Services\CfocnFilteredMatcher;
use App\Services\InfrastructureFilter;
use App\Services\VerificationStatusResolver;
use Tests\TestCase;

/**
 * All synthetic data — none of these use the real CFOCN fixtures. Covers
 * the new infrastructure selector + filtered matching + verification
 * status engine, which is entirely separate from
 * ImportFileController::verifyData.
 */
class InfrastructureFilterMatchingTest extends TestCase
{
    /**
     * This project's test suite has no RefreshDatabase isolation — it runs
     * against the real dev database. These tests therefore never assume a
     * table starts empty; they compare counts before/after, and clean up
     * any rows they insert.
     */
    private array $cleanupWorkOrders = [];

    protected function tearDown(): void
    {
        if ($this->cleanupWorkOrders !== []) {
            VerifiedInfrastructure::whereIn('work_order', $this->cleanupWorkOrders)->delete();
        }

        parent::tearDown();
    }

    private function builderRows(): array
    {
        return [
            ['jobs' => 'JOB1', 'infrastructure' => 'CFOCN'],
            ['jobs' => 'JOB2', 'infrastructure' => 'CFOCN 7'],
            ['jobs' => 'JOB3', 'infrastructure' => 'OCIC'],
            ['jobs' => 'JOB4', 'infrastructure' => null],
            ['jobs' => 'JOB5', 'infrastructure' => ''],
            ['jobs' => 'JOB6', 'infrastructure' => '   '],
        ];
    }

    // 1. CFOCN Exact includes "CFOCN" and excludes "CFOCN 7" and "OCIC".
    public function test_exact_rule_only_matches_the_exact_infrastructure_value(): void
    {
        $filter = new InfrastructureFilter();
        $result = $filter->apply($this->builderRows(), ['CFOCN'], false, InfrastructureFilter::RULE_EXACT);

        $jobs = array_column($result['eligible'], 'jobs');

        $this->assertSame(['JOB1'], $jobs);
    }

    // 2. CFOCN Contains includes "CFOCN" and "CFOCN 7", excludes "OCIC".
    public function test_contains_rule_matches_values_containing_the_selection(): void
    {
        $filter = new InfrastructureFilter();
        $result = $filter->apply($this->builderRows(), ['CFOCN'], false, InfrastructureFilter::RULE_CONTAINS);

        $jobs = array_column($result['eligible'], 'jobs');

        $this->assertEqualsCanonicalizing(['JOB1', 'JOB2'], $jobs);
    }

    // 3. Multiple selections include rows matching any selected value.
    public function test_multiple_selections_match_any_selected_value(): void
    {
        $filter = new InfrastructureFilter();
        $result = $filter->apply($this->builderRows(), ['CFOCN', 'OCIC'], false, InfrastructureFilter::RULE_EXACT);

        $jobs = array_column($result['eligible'], 'jobs');

        $this->assertEqualsCanonicalizing(['JOB1', 'JOB3'], $jobs);
    }

    // 4. All infrastructures includes every eligible row.
    public function test_all_infrastructures_includes_every_row_with_a_usable_value(): void
    {
        $filter = new InfrastructureFilter();
        $result = $filter->apply($this->builderRows(), [], true, InfrastructureFilter::RULE_EXACT);

        $jobs = array_column($result['eligible'], 'jobs');

        $this->assertEqualsCanonicalizing(['JOB1', 'JOB2', 'JOB3'], $jobs);
        $this->assertSame(3, $result['excluded_missing_or_invalid']);
    }

    // 5. Null, blank, and whitespace infrastructure values are safe.
    public function test_null_blank_and_whitespace_infrastructure_values_are_excluded_safely(): void
    {
        $filter = new InfrastructureFilter();
        $result = $filter->apply($this->builderRows(), [], true, InfrastructureFilter::RULE_EXACT);

        $jobs = array_column($result['eligible'], 'jobs');

        $this->assertNotContains('JOB4', $jobs);
        $this->assertNotContains('JOB5', $jobs);
        $this->assertNotContains('JOB6', $jobs);
        $this->assertSame(3, $result['excluded_missing_or_invalid']);
    }

    // 6. Matching compares Work Order to jobs, not PORT.
    public function test_matching_compares_work_order_to_jobs_not_port(): void
    {
        $matcher = new CfocnFilteredMatcher();

        $companyRows = [
            ['Work Order' => 'JOB1', 'PORT' => 'JOB9'],
        ];
        $builderRows = [
            ['jobs' => 'JOB9'], // only matches via PORT if the matcher were wrong
        ];

        $result = $matcher->match($companyRows, $builderRows);

        $this->assertCount(0, $result['match']);
        $this->assertCount(1, $result['not_match']);
    }

    // 7. Match and Not match are builder-driven (unique CFOCN job numbers),
    // and a company record with no corresponding CFOCN builder row (JOB9)
    // is excluded from both.
    public function test_match_and_not_match_are_consistent_and_exclude_non_cfocn_company_records(): void
    {
        $matcher = new CfocnFilteredMatcher();

        $companyRows = [
            ['Work Order' => 'JOB1'],
            ['Work Order' => 'JOB2'],
            ['Work Order' => 'JOB9'],
        ];
        $builderRows = [
            ['jobs' => 'JOB1'],
            ['jobs' => 'JOB2'],
            ['jobs' => 'JOB3'],
        ];

        $result = $matcher->match($companyRows, $builderRows);

        $this->assertCount(2, $result['match']);
        $this->assertCount(1, $result['not_match']);
        $this->assertSame(['JOB3'], array_column($result['not_match'], 'Work Order'));
        $this->assertSame(2, $result['matched_unique_count']);
        $this->assertSame(1, $result['not_match_unique_count']);
        $this->assertSame(3, $result['total_cfocn_unique_count']);
        $this->assertNotContains('JOB9', array_column($result['match'], 'Work Order'));
        $this->assertNotContains('JOB9', array_column($result['not_match'], 'Work Order'));
    }

    // 8. Duplicate CFOCN job numbers count once; blank/missing company Work
    // Orders create no false matches.
    public function test_duplicate_blank_and_missing_work_orders_create_no_false_matches(): void
    {
        $matcher = new CfocnFilteredMatcher();

        $companyRows = [
            ['Work Order' => 'JOB1'],
            ['Work Order' => 'JOB1'], // duplicate
            ['Work Order' => ''],     // blank
            ['Work Order' => '   '],  // whitespace-only
            [],                        // missing key entirely
        ];
        $builderRows = [
            ['jobs' => 'JOB1'],
            ['jobs' => 'JOB1'], // duplicate builder job number
        ];

        $result = $matcher->match($companyRows, $builderRows);

        // Sheet may contain both duplicate builder rows (reference only)...
        $this->assertCount(2, $result['match']);
        $this->assertCount(0, $result['not_match']);
        // ...but the unique job-number count is 1.
        $this->assertSame(1, $result['matched_unique_count']);
        $this->assertSame(0, $result['not_match_unique_count']);
        $this->assertSame(1, $result['total_cfocn_unique_count']);
    }

    // 9. Filtering does not delete or change builder records.
    public function test_filtering_never_touches_the_builders_table(): void
    {
        $before = \DB::table('builders')->count();

        $filter = new InfrastructureFilter();
        $filter->apply($this->builderRows(), ['CFOCN'], false, InfrastructureFilter::RULE_EXACT);

        $this->assertSame($before, \DB::table('builders')->count());
    }

    // 10. A matched record without a verified entry shows Unverified.
    public function test_matched_record_without_verified_entry_is_unverified(): void
    {
        $workOrder = 'TEST-UNVERIFIED-'.uniqid();
        $resolver = new VerificationStatusResolver();

        $statuses = $resolver->resolve([$workOrder]);

        $this->assertSame(VerificationStatusResolver::UNVERIFIED, $statuses[$workOrder]);
    }

    // 11. A verified entry shows Verified.
    public function test_matched_record_with_verified_entry_is_verified(): void
    {
        $workOrder = 'TEST-VERIFIED-'.uniqid();
        $this->cleanupWorkOrders[] = $workOrder;

        VerifiedInfrastructure::create(['work_order' => $workOrder, 'verified_infrastructure' => 'CFOCN']);

        $resolver = new VerificationStatusResolver();
        $statuses = $resolver->resolve([$workOrder]);

        $this->assertSame(VerificationStatusResolver::VERIFIED, $statuses[$workOrder]);
    }

    // 12. Verified status is never set from the builder filter alone.
    public function test_verified_status_is_never_set_by_the_filter_or_matcher_alone(): void
    {
        $workOrder = 'TEST-NEVER-AUTO-VERIFIED-'.uniqid();
        $before = \DB::table('verified_infrastructures')->count();

        $filter = new InfrastructureFilter();
        $matcher = new CfocnFilteredMatcher();

        $filter->apply($this->builderRows(), ['CFOCN'], false, InfrastructureFilter::RULE_EXACT);
        $matcher->match([['Work Order' => $workOrder]], [['jobs' => $workOrder]]);

        $this->assertSame($before, \DB::table('verified_infrastructures')->count());

        $resolver = new VerificationStatusResolver();
        $this->assertSame(VerificationStatusResolver::UNVERIFIED, $resolver->resolve([$workOrder])[$workOrder]);
    }
}
