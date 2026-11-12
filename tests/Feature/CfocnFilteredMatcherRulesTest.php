<?php

namespace Tests\Feature;

use App\Services\CfocnFilteredMatcher;
use Tests\TestCase;

/**
 * Synthetic data only. Covers the manager-confirmed matching rules for the
 * infrastructure-filtered CFOCN pipeline: builder-driven Match/Not match by
 * unique job number, with no billing data involved in the decision.
 */
class CfocnFilteredMatcherRulesTest extends TestCase
{
    // 1. A CFOCN job found in the company file is in Match.
    public function test_a_cfocn_job_found_in_the_company_file_is_in_match(): void
    {
        $matcher = new CfocnFilteredMatcher();

        $result = $matcher->match(
            [['Work Order' => 'JOB1']],
            [['jobs' => 'JOB1']]
        );

        $this->assertCount(1, $result['match']);
        $this->assertSame('JOB1', $result['match'][0]['Work Order']);
        $this->assertCount(0, $result['not_match']);
    }

    // 2. A CFOCN job not found in the company file is in Not match and has
    // Infrastructure = CFOCN.
    public function test_a_cfocn_job_not_found_in_the_company_file_is_not_match_with_cfocn_infrastructure(): void
    {
        $matcher = new CfocnFilteredMatcher();

        $result = $matcher->match(
            [['Work Order' => 'SOME-OTHER-JOB']],
            [['jobs' => 'JOB1', 'infrastructure' => 'CFOCN']]
        );

        $this->assertCount(0, $result['match']);
        $this->assertCount(1, $result['not_match']);
        $this->assertSame('JOB1', $result['not_match'][0]['Work Order']);
        $this->assertSame('CFOCN', $result['not_match'][0]['Infrastructure']);
    }

    // 3. A company work order that is not in CFOCN does not appear in Match
    // or Not match.
    public function test_a_company_work_order_not_in_cfocn_does_not_appear_in_match_or_not_match(): void
    {
        $matcher = new CfocnFilteredMatcher();

        $result = $matcher->match(
            [['Work Order' => 'JOB1'], ['Work Order' => 'NOT-IN-CFOCN']],
            [['jobs' => 'JOB1']]
        );

        $allWorkOrders = array_merge(
            array_column($result['match'], 'Work Order'),
            array_column($result['not_match'], 'Work Order')
        );

        $this->assertNotContains('NOT-IN-CFOCN', $allWorkOrders);
    }

    // 4. Duplicate CFOCN job numbers are counted once.
    public function test_duplicate_cfocn_job_numbers_are_counted_once(): void
    {
        $matcher = new CfocnFilteredMatcher();

        $result = $matcher->match(
            [['Work Order' => 'JOB1']],
            [['jobs' => 'JOB1'], ['jobs' => 'JOB1'], ['jobs' => ' JOB1 ']]
        );

        $this->assertSame(1, $result['matched_unique_count']);
        $this->assertSame(1, $result['total_cfocn_unique_count']);
    }

    // 5. Blank and whitespace work orders are ignored.
    public function test_blank_and_whitespace_work_orders_are_ignored(): void
    {
        $matcher = new CfocnFilteredMatcher();

        $result = $matcher->match(
            [['Work Order' => '']],
            [['jobs' => ''], ['jobs' => '   '], ['jobs' => 'JOB1']]
        );

        $this->assertSame(1, $result['total_cfocn_unique_count']);
        $this->assertSame(0, $result['matched_unique_count']);
        $this->assertSame(1, $result['not_match_unique_count']);
    }

    // 6. Matched + Not match equals the total unique CFOCN job numbers.
    public function test_matched_plus_not_match_equals_total_unique_cfocn_job_numbers(): void
    {
        $matcher = new CfocnFilteredMatcher();

        $result = $matcher->match(
            [['Work Order' => 'JOB1'], ['Work Order' => 'JOB3']],
            [['jobs' => 'JOB1'], ['jobs' => 'JOB2'], ['jobs' => 'JOB3'], ['jobs' => 'JOB3']]
        );

        $this->assertSame(
            $result['total_cfocn_unique_count'],
            $result['matched_unique_count'] + $result['not_match_unique_count']
        );
        $this->assertSame(3, $result['total_cfocn_unique_count']);
        $this->assertSame(2, $result['matched_unique_count']);
        $this->assertSame(1, $result['not_match_unique_count']);
    }
}
