<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Import;
use App\Models\ImportRow;
use Illuminate\Support\Facades\DB;

class ImportRecorder
{
    /**
     * @param  array{match: array, not_match: array, bill: array}  $result
     */
    public function record(Import $import, array $result): Import
    {
        DB::transaction(function () use ($import, $result) {
            $import->update([
                'match_count' => count($result['match']),
                'not_match_count' => count($result['not_match']),
                'bill_count' => count($result['bill']),
                'status' => 'completed',
            ]);

            $this->insertRows($import, 'match', $result['match']);
            $this->insertRows($import, 'not_match', $result['not_match']);
            $this->insertRows($import, 'bill', $result['bill']);

            AuditLog::create([
                'import_id' => $import->id,
                'user_id' => $import->created_by,
                'action' => 'import.created',
                'details' => [
                    'counts' => [
                        'match' => count($result['match']),
                        'not_match' => count($result['not_match']),
                        'bill' => count($result['bill']),
                    ],
                ],
                'created_at' => now(),
            ]);
        });

        return $import->refresh();
    }

    public function recordFailure(Import $import, string $message): Import
    {
        $import->update([
            'status' => 'failed',
            'error_message' => $message,
        ]);

        AuditLog::create([
            'import_id' => $import->id,
            'user_id' => $import->created_by,
            'action' => 'import.failed',
            'details' => ['message' => $message],
            'created_at' => now(),
        ]);

        return $import->refresh();
    }

    private function insertRows(Import $import, string $matchType, array $rows): void
    {
        $now = now();

        // row_data is a plain longText column (not MySQL's native JSON type)
        // specifically so this exact byte sequence — including key order —
        // round-trips unchanged; see the migration comment for why.
        foreach (array_chunk($rows, 500) as $chunk) {
            ImportRow::insert(array_map(fn ($row) => [
                'import_id' => $import->id,
                'match_type' => $matchType,
                'row_data' => json_encode($row),
                'created_at' => $now,
            ], $chunk));
        }
    }
}
