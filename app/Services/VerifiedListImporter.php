<?php

namespace App\Services;

use App\Models\VerifiedInfrastructure;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Imports a trusted "Work Order + Verified Infrastructure" list, upserting
 * one verified_infrastructures row per entry. This is the only way a record
 * can ever become "Verified" (see VerificationStatusResolver).
 */
class VerifiedListImporter
{
    public function import(UploadedFile $file): int
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, true, false);

        $header = array_map(fn ($h) => is_string($h) ? trim($h) : $h, array_shift($data) ?: []);
        $workOrderIndex = array_search('Work Order', $header, true);
        $infraIndex = array_search('Verified Infrastructure', $header, true);

        if ($workOrderIndex === false || $infraIndex === false) {
            throw new \InvalidArgumentException('The verified list must have "Work Order" and "Verified Infrastructure" columns.');
        }

        $count = 0;

        foreach ($data as $row) {
            $workOrder = trim((string) ($row[$workOrderIndex] ?? ''));
            $infrastructure = trim((string) ($row[$infraIndex] ?? ''));

            if ($workOrder === '' || $infrastructure === '') {
                continue;
            }

            VerifiedInfrastructure::updateOrCreate(
                ['work_order' => $workOrder],
                ['verified_infrastructure' => $infrastructure]
            );

            $count++;
        }

        return $count;
    }
}
