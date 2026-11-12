<?php

namespace App\Services;

/**
 * A separate, from-scratch matching engine for the new infrastructure
 * selector feature. Operates only on rows handed to it in memory — never
 * reads or writes the `builders` table — so it cannot be affected by, or
 * affect, the existing (frozen) ImportFileController::verifyData matcher.
 *
 * Source of truth is the filtered CFOCN builder list: every eligible builder
 * row is either found in the company file's Work Order list (Match) or not
 * (Not match). Company records that are not part of the eligible builder
 * list are never counted as Match or Not match. Billing data plays no part
 * in this decision.
 */
class CfocnFilteredMatcher
{
    /**
     * @param array $companyRows Rows parsed from the just-uploaded company file, each with a 'Work Order' key.
     * @param array $eligibleBuilderRows Rows already passed through InfrastructureFilter, each with a 'jobs' key.
     * @return array{
     *     match: array,
     *     not_match: array,
     *     matched_unique_count: int,
     *     not_match_unique_count: int,
     *     total_cfocn_unique_count: int,
     * }
     */
    public function match(array $companyRows, array $eligibleBuilderRows): array
    {
        $companyWorkOrders = [];
        foreach ($companyRows as $row) {
            $workOrder = $this->normalize($row['Work Order'] ?? null);

            if ($workOrder !== '') {
                $companyWorkOrders[$workOrder] = true;
            }
        }

        $match = [];
        $notMatch = [];
        $matchedJobs = [];
        $notMatchedJobs = [];

        foreach ($eligibleBuilderRows as $row) {
            $job = $this->normalize($row['jobs'] ?? null);

            if ($job === '') {
                // Ignore builder rows with no usable job number.
                continue;
            }

            $outputRow = $row;
            $outputRow['Work Order'] = $job;
            $outputRow['Infrastructure'] = 'CFOCN';

            if (isset($companyWorkOrders[$job])) {
                $match[] = $outputRow;
                $matchedJobs[$job] = true;
            } else {
                $notMatch[] = $outputRow;
                $notMatchedJobs[$job] = true;
            }
        }

        return [
            'match' => $match,
            'not_match' => $notMatch,
            // Headline counts: unique job numbers, not raw row counts. The
            // sheets above may contain more rows than this when the builder
            // file has duplicate job numbers for the same infrastructure.
            'matched_unique_count' => count($matchedJobs),
            'not_match_unique_count' => count($notMatchedJobs),
            'total_cfocn_unique_count' => count($matchedJobs) + count($notMatchedJobs),
        ];
    }

    private function normalize(mixed $value): string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return '';
        }

        return trim((string) $value);
    }
}
