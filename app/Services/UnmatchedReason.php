<?php

namespace App\Services;

/**
 * Explains why a CFOCN company-file row was left unmatched by the (frozen)
 * matcher in ImportFileController::verifyData. This never decides whether a
 * row matches — it only looks, after the fact, at rows the matcher already
 * left in "not match", using the same exact job-number comparison the
 * matcher uses ($builderRow['jobs'] against the company row's work order).
 */
class UnmatchedReason
{
    /**
     * @param mixed $workOrder The unmatched company row's "Work Order" value.
     * @param array $builderRows All imported builder rows, across every infrastructure
     *                           (each with 'jobs' and 'infrastructure' keys) — not just
     *                           the CFOCN-filtered subset the matcher matches against.
     */
    public function reasonFor(mixed $workOrder, array $builderRows): string
    {
        foreach ($builderRows as $builderRow) {
            $jobs = $builderRow['jobs'] ?? null;

            if ($jobs !== null && $jobs == $workOrder) {
                $infrastructure = $builderRow['infrastructure'] ?? null;

                if ($infrastructure !== 'CFOCN') {
                    return "Belongs to other infrastructure: {$infrastructure}";
                }
            }
        }

        return 'Not in builder';
    }
}
