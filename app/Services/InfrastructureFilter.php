<?php

namespace App\Services;

/**
 * Filters just-uploaded builder rows down to the ones eligible for matching,
 * by the user's chosen infrastructure selection and rule. Operates purely
 * on the in-memory rows handed to it — it never reads the `builders` table
 * and never deletes, truncates, or modifies any builder record.
 */
class InfrastructureFilter
{
    public const RULE_EXACT = 'exact';

    public const RULE_CONTAINS = 'contains';

    public const RULES = [self::RULE_EXACT, self::RULE_CONTAINS];

    /**
     * @param array $builderRows Raw rows from the just-uploaded builder file only.
     * @param string[] $selected Infrastructure values to filter by. Ignored when $all is true.
     * @param bool $all "All infrastructures" — every row with a usable (non-blank) value passes.
     * @return array{eligible: array, excluded_missing_or_invalid: int, counts_by_infrastructure: array<string,int>}
     */
    public function apply(array $builderRows, array $selected, bool $all, string $rule): array
    {
        if (! in_array($rule, self::RULES, true)) {
            throw new \InvalidArgumentException("Unknown rule \"{$rule}\". Use \"exact\" or \"contains\".");
        }

        $selected = array_values(array_filter(array_map(fn ($v) => trim((string) $v), $selected), fn ($v) => $v !== ''));

        if (! $all && count($selected) === 0) {
            throw new \InvalidArgumentException('At least one infrastructure must be selected unless "All infrastructures" is chosen.');
        }

        $eligible = [];
        $excludedMissingOrInvalid = 0;
        $countsByInfrastructure = [];

        foreach ($builderRows as $row) {
            $value = $this->normalize($row['infrastructure'] ?? null);

            // Null, blank, and whitespace-only values are never eligible,
            // under any rule or selection — they can't be reliably matched
            // to a chosen infrastructure.
            if ($value === '') {
                $excludedMissingOrInvalid++;

                continue;
            }

            if (! $all && ! $this->matchesAnySelected($value, $selected, $rule)) {
                continue;
            }

            $eligible[] = $row;
            $countsByInfrastructure[$value] = ($countsByInfrastructure[$value] ?? 0) + 1;
        }

        return [
            'eligible' => $eligible,
            'excluded_missing_or_invalid' => $excludedMissingOrInvalid,
            'counts_by_infrastructure' => $countsByInfrastructure,
        ];
    }

    private function matchesAnySelected(string $value, array $selected, string $rule): bool
    {
        foreach ($selected as $candidate) {
            if ($rule === self::RULE_EXACT) {
                if ($value === $candidate) {
                    return true;
                }
            } elseif (mb_stripos($value, $candidate) !== false) {
                return true;
            }
        }

        return false;
    }

    private function normalize(mixed $value): string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return '';
        }

        return trim((string) $value);
    }
}
