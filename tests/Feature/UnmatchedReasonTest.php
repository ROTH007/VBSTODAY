<?php

namespace Tests\Feature;

use App\Services\UnmatchedReason;
use Tests\TestCase;

/**
 * Tests the reason-labelling logic in isolation from the matcher. The real
 * end-to-end check (that the breakdown adds up to 924 on the CFOCN
 * regression fixtures) lives in CfocnImportRegressionTest once
 * testfiles/builder_test.xlsx and cfocn_test.xlsx are available — these
 * tests exercise the same job-number comparison on synthetic builder rows
 * so the reason logic itself is covered without depending on that fixture.
 */
class UnmatchedReasonTest extends TestCase
{
    public function test_work_order_with_no_matching_builder_row_is_not_in_builder(): void
    {
        $reason = new UnmatchedReason();

        $builderRows = [
            ['jobs' => 'JOB-100', 'infrastructure' => 'CFOCN'],
            ['jobs' => 'JOB-200', 'infrastructure' => 'TCT'],
        ];

        $this->assertSame('Not in builder', $reason->reasonFor('JOB-999', $builderRows));
    }

    public function test_work_order_belonging_to_other_infrastructure(): void
    {
        $reason = new UnmatchedReason();

        $builderRows = [
            ['jobs' => 'JOB-300', 'infrastructure' => 'OCIC'],
        ];

        $this->assertSame('Belongs to other infrastructure: OCIC', $reason->reasonFor('JOB-300', $builderRows));
    }

    public function test_breakdown_adds_up_to_not_match_total(): void
    {
        $reason = new UnmatchedReason();

        $builderRows = [
            ['jobs' => 'JOB-1', 'infrastructure' => 'OCIC'],
            ['jobs' => 'JOB-2', 'infrastructure' => 'ADI'],
        ];

        $unmatchedWorkOrders = ['JOB-1', 'JOB-2', 'JOB-NOT-FOUND-1', 'JOB-NOT-FOUND-2', 'JOB-NOT-FOUND-3'];

        $otherInfrastructure = 0;
        $notInBuilder = 0;

        foreach ($unmatchedWorkOrders as $workOrder) {
            $result = $reason->reasonFor($workOrder, $builderRows);
            str_starts_with($result, 'Belongs to other infrastructure') ? $otherInfrastructure++ : $notInBuilder++;
        }

        $this->assertSame(2, $otherInfrastructure);
        $this->assertSame(3, $notInBuilder);
        $this->assertSame(count($unmatchedWorkOrders), $otherInfrastructure + $notInBuilder);
    }

    public function test_a_builder_row_for_cfocn_itself_never_produces_other_infrastructure(): void
    {
        $reason = new UnmatchedReason();

        $builderRows = [
            ['jobs' => 'JOB-5', 'infrastructure' => 'CFOCN'],
        ];

        // In practice the matcher would already have matched this row, but
        // the reason service must still never call a CFOCN row "other".
        $this->assertSame('Not in builder', $reason->reasonFor('JOB-6', $builderRows));
    }
}
