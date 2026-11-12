<?php

namespace App\Services;

/**
 * Orchestrates one run of the new infrastructure-filtered CFOCN matcher:
 * filter -> match -> resolve verification status -> summary. This is a
 * separate pipeline from ImportFileController::verifyData; it never reads
 * or writes the `builders` table.
 */
class CfocnFilteredImportRunner
{
    public function __construct(
        private InfrastructureFilter $filter,
        private CfocnFilteredMatcher $matcher,
        private VerificationStatusResolver $verification,
    ) {
    }

    /**
     * @param array $builderRows Raw rows parsed from the just-uploaded builder file only.
     * @param array $companyRows Raw rows parsed from the just-uploaded CFOCN company file only.
     * @param string[] $selectedInfrastructures
     */
    public function run(array $builderRows, array $companyRows, array $selectedInfrastructures, bool $all, string $rule): array
    {
        $filtered = $this->filter->apply($builderRows, $selectedInfrastructures, $all, $rule);
        $result = $this->matcher->match($companyRows, $filtered['eligible']);

        $matchedWorkOrders = array_values(array_unique(array_map(
            fn ($row) => $row['Work Order'],
            $result['match']
        )));
        $verificationStatuses = $this->verification->resolve($matchedWorkOrders);

        $verifiedCount = 0;
        $unverifiedCount = 0;
        foreach ($verificationStatuses as $status) {
            $status === VerificationStatusResolver::VERIFIED ? $verifiedCount++ : $unverifiedCount++;
        }

        return [
            'match' => $result['match'],
            'not_match' => $result['not_match'],
            'verification' => $verificationStatuses,
            'summary' => [
                // Headline counts, per the manager-confirmed definition:
                // unique CFOCN job numbers from the filtered builder list,
                // checked against the company file's Work Order list.
                // Billing data plays no part in these counts.
                'total_cfocn_work_orders' => $result['total_cfocn_unique_count'],
                'matched' => $result['matched_unique_count'],
                'not_matched' => $result['not_match_unique_count'],
                'matched_plus_not_matched_equals_total' =>
                    ($result['matched_unique_count'] + $result['not_match_unique_count']) === $result['total_cfocn_unique_count'],
                // Reference-only row counts: the sheets may hold more rows
                // than the unique counts above when the builder file has
                // duplicate job numbers.
                'total_builder_rows_after_filter' => count($filtered['eligible']),
                'not_match_sheet_row_count' => count($result['not_match']),
                'match_sheet_row_count' => count($result['match']),
                'counts_by_infrastructure' => $filtered['counts_by_infrastructure'],
                'missing_or_invalid_infrastructure_count' => $filtered['excluded_missing_or_invalid'],
                'infrastructure_unverified' => $unverifiedCount,
            ],
        ];
    }
}
