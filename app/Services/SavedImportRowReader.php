<?php

namespace App\Services;

use App\Models\SavedImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Reads display rows out of a saved import's stored workbook. This only
 * reads sheet data the (frozen) controller already produced — it never
 * recomputes or alters a match.
 */
class SavedImportRowReader
{
    private const SHEET_NAMES = [
        'bill' => 'Bill',
        'match' => 'Match',
        'not_match' => 'Not match',
    ];

    public function __construct(private SavedImportRecorder $recorder)
    {
    }

    /**
     * @return array{columns: string[], rows: array<int, array<string, mixed>>, total: int}
     */
    public function read(SavedImport $savedImport, string $sheetKey, ?string $search = null, int $page = 1, int $perPage = 10): array
    {
        $sheetName = self::SHEET_NAMES[$sheetKey] ?? null;

        if (! $sheetName) {
            return ['columns' => [], 'rows' => [], 'total' => 0];
        }

        $path = Storage::path($this->recorder->workbookPath($savedImport->id));

        if (! file_exists($path)) {
            return ['columns' => [], 'rows' => [], 'total' => 0];
        }

        $workbook = IOFactory::load($path);
        $sheet = $workbook->getSheetByName($sheetName);

        if (! $sheet) {
            return ['columns' => [], 'rows' => [], 'total' => 0];
        }

        $data = $sheet->toArray(null, true, true, false);
        $columns = array_shift($data) ?: [];

        $rows = array_map(function ($row) use ($columns) {
            $assoc = [];
            foreach ($columns as $i => $col) {
                $assoc[$col] = $row[$i] ?? null;
            }

            return $assoc;
        }, $data);

        if ($sheetKey === 'not_match') {
            $columns[] = 'Reason';
            $reasons = DB::table('saved_import_unmatched_reasons')
                ->where('saved_import_id', $savedImport->id)
                ->pluck('reason', 'work_order');

            $rows = array_map(function ($row) use ($reasons) {
                $row['Reason'] = $reasons->get((string) ($row['Work Order'] ?? ''), '—');

                return $row;
            }, $rows);
        }

        if ($search !== null && $search !== '') {
            $needle = mb_strtolower($search);
            $rows = array_values(array_filter($rows, function ($row) use ($needle) {
                foreach ($row as $value) {
                    if (mb_strpos(mb_strtolower((string) $value), $needle) !== false) {
                        return true;
                    }
                }

                return false;
            }));
        }

        $total = count($rows);
        $page = max(1, $page);
        $slice = array_slice($rows, ($page - 1) * $perPage, $perPage);

        return ['columns' => $columns, 'rows' => $slice, 'total' => $total];
    }
}
