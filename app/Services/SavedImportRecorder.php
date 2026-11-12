<?php

namespace App\Services;

use App\Models\SavedImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Saves a record of a completed /importExecute run, by reading the row
 * counts out of the workbook the (frozen) controller already generated.
 * This never recomputes a match — it only counts rows the server already
 * decided belong to each sheet.
 */
class SavedImportRecorder
{
    public function __construct(private UnmatchedReason $unmatchedReason)
    {
    }

    public function record(string $filePath, string $company, ?string $status, ?int $userId = null): SavedImport
    {
        $workbook = IOFactory::load($filePath);
        $counts = $this->countSheetRows($workbook);

        $savedImport = SavedImport::create([
            'company' => $company,
            'status' => $status,
            'match_count' => $counts['match'],
            'not_match_count' => $counts['not_match'],
            'bill_count' => $counts['bill'],
            'created_by' => $userId,
        ]);

        Storage::makeDirectory('saved_imports');
        copy($filePath, Storage::path($this->workbookPath($savedImport->id)));

        // CFOCN only (business rule). The builders table still holds this
        // run's rows at this point — the frozen controller only truncates it
        // at the START of the *next* /importExecute request — so this is
        // the only safe point to read them back for the unmatched reasons.
        if ($company === 'cfocn') {
            $breakdown = $this->recordUnmatchedReasons($savedImport, $workbook);
            $savedImport->update([
                'other_infrastructure_count' => $breakdown['other_infrastructure'],
                'not_in_builder_count' => $breakdown['not_in_builder'],
            ]);
        }

        app(AuditLogger::class)->record(
            action: 'create',
            model: 'SavedImport',
            modelId: $savedImport->id,
            changes: ['counts' => $counts],
            userId: $userId,
        );

        return $savedImport;
    }

    public function workbookPath(int $savedImportId): string
    {
        return 'saved_imports/'.$savedImportId.'.xlsx';
    }

    /**
     * @return array{match: int, not_match: int, bill: int}
     */
    private function countSheetRows(Spreadsheet $workbook): array
    {
        $count = function (string $sheetName) use ($workbook) {
            $sheet = $workbook->getSheetByName($sheetName);
            if (! $sheet) {
                return 0;
            }

            return max(0, $sheet->getHighestDataRow() - 1);
        };

        return [
            'match' => $count('Match'),
            'not_match' => $count('Not match'),
            'bill' => $count('Bill'),
        ];
    }

    /**
     * @return array{other_infrastructure: int, not_in_builder: int}
     */
    private function recordUnmatchedReasons(SavedImport $savedImport, Spreadsheet $workbook): array
    {
        $sheet = $workbook->getSheetByName('Not match');

        if (! $sheet) {
            return ['other_infrastructure' => 0, 'not_in_builder' => 0];
        }

        $data = $sheet->toArray(null, true, true, false);
        $columns = array_shift($data) ?: [];
        $workOrderIndex = array_search('Work Order', $columns, true);

        if ($workOrderIndex === false) {
            return ['other_infrastructure' => 0, 'not_in_builder' => 0];
        }

        $builderRows = DB::table('builders')->get()->map(fn ($r) => (array) $r)->toArray();

        $otherInfrastructure = 0;
        $notInBuilder = 0;
        $now = now();

        foreach (array_chunk($data, 500) as $chunk) {
            $inserts = [];

            foreach ($chunk as $row) {
                $workOrder = $row[$workOrderIndex] ?? null;
                $reason = $this->unmatchedReason->reasonFor($workOrder, $builderRows);

                if (str_starts_with($reason, 'Belongs to other infrastructure')) {
                    $otherInfrastructure++;
                } else {
                    $notInBuilder++;
                }

                $inserts[] = [
                    'saved_import_id' => $savedImport->id,
                    'work_order' => (string) $workOrder,
                    'reason' => $reason,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($inserts) {
                DB::table('saved_import_unmatched_reasons')->insert($inserts);
            }
        }

        return ['other_infrastructure' => $otherInfrastructure, 'not_in_builder' => $notInBuilder];
    }
}
