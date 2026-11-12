<?php

namespace App\Services;

/**
 * Reads the distinct Infrastructure values out of a just-uploaded builder
 * file's rows, for the UI's infrastructure selector. Never reads from the
 * database and never touches the (frozen) matching logic.
 */
class InfrastructureOptions
{
    /**
     * @param array $builderRows Raw rows parsed from the just-uploaded builder file.
     * @return string[] Distinct, trimmed, non-blank infrastructure values, sorted.
     */
    public function distinctValues(array $builderRows): array
    {
        $values = [];

        foreach ($builderRows as $row) {
            $value = $this->normalize($row['infrastructure'] ?? null);

            if ($value !== '') {
                $values[$value] = true;
            }
        }

        $list = array_keys($values);
        sort($list);

        return $list;
    }

    private function normalize(mixed $value): string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return '';
        }

        return trim((string) $value);
    }
}
