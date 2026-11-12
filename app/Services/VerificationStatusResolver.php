<?php

namespace App\Services;

use App\Models\VerifiedInfrastructure;

/**
 * Decides whether a matched record's infrastructure is "Verified" or
 * "Unverified". A record is only ever Verified because a trusted verified
 * entry exists for its Work Order — never because it passed the
 * infrastructure filter or the matcher.
 */
class VerificationStatusResolver
{
    public const VERIFIED = 'Verified';

    public const UNVERIFIED = 'Unverified';

    /**
     * @param string[] $workOrders Work Order values of matched records (may contain blanks/duplicates).
     * @return array<string,string> work order => 'Verified'|'Unverified'
     */
    public function resolve(array $workOrders): array
    {
        $workOrders = array_values(array_unique(array_filter(
            array_map(fn ($v) => trim((string) $v), $workOrders),
            fn ($v) => $v !== ''
        )));

        if ($workOrders === []) {
            return [];
        }

        $verifiedSet = VerifiedInfrastructure::whereIn('work_order', $workOrders)
            ->pluck('work_order')
            ->flip();

        $statuses = [];

        foreach ($workOrders as $workOrder) {
            $statuses[$workOrder] = $verifiedSet->has($workOrder) ? self::VERIFIED : self::UNVERIFIED;
        }

        return $statuses;
    }
}
