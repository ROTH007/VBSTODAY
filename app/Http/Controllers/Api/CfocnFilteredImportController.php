<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RunCfocnFilteredImportRequest;
use App\Imports\RawRowsImport;
use App\Models\CfocnFilteredRun;
use App\Services\CfocnFilteredImportRunner;
use App\Services\InfrastructureOptions;
use App\Services\VerifiedListImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * New, separate endpoints for the infrastructure selector feature. These
 * never call ImportFileController::verifyData and never read or write the
 * `builders` table — every row comes straight from the file uploaded in
 * the current request.
 */
class CfocnFilteredImportController extends Controller
{
    public function inspectBuilder(Request $request, InfrastructureOptions $options): JsonResponse
    {
        $request->validate(['builder' => ['required', 'file']]);

        $rows = Excel::toArray(new RawRowsImport(), $request->file('builder'))[0] ?? [];

        return response()->json([
            'infrastructures' => $options->distinctValues($rows),
        ]);
    }

    public function run(RunCfocnFilteredImportRequest $request, CfocnFilteredImportRunner $runner): JsonResponse
    {
        $builderRows = Excel::toArray(new RawRowsImport(), $request->file('builder'))[0] ?? [];
        $companyRows = Excel::toArray(new RawRowsImport(), $request->file('cfo'))[0] ?? [];

        // The default heading formatter slugifies "Work Order" to
        // "work_order" — normalize it back to the 'Work Order' key the
        // rest of this feature (and its tests) use.
        $companyRows = array_map(function ($row) {
            $row['Work Order'] = $row['Work Order'] ?? $row['work_order'] ?? null;

            return $row;
        }, $companyRows);

        $all = $request->boolean('all_infrastructures');
        $selected = $all ? [] : $request->input('infrastructures', []);
        $rule = $request->input('rule', 'exact');

        try {
            $result = $runner->run($builderRows, $companyRows, $selected, $all, $rule);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // Record this run so the Overview page can show the latest
        // infrastructure-filter result. This is purely an observation of
        // the runner's output — it does not affect matching.
        CfocnFilteredRun::create([
            'total_cfocn_work_orders' => $result['summary']['total_cfocn_work_orders'],
            'matched' => $result['summary']['matched'],
            'not_matched' => $result['summary']['not_matched'],
            'matched_plus_not_matched_equals_total' => $result['summary']['matched_plus_not_matched_equals_total'],
            'not_match_rows' => $result['not_match'],
            'match_rows' => $result['match'],
        ]);

        return response()->json($result);
    }

    public function latest(): JsonResponse
    {
        $run = CfocnFilteredRun::latest('id')->first();

        if (! $run) {
            return response()->json(['run' => null]);
        }

        return response()->json([
            'run' => [
                'id' => $run->id,
                'created_at' => $run->created_at,
                'summary' => [
                    'total_cfocn_work_orders' => $run->total_cfocn_work_orders,
                    'matched' => $run->matched,
                    'not_matched' => $run->not_matched,
                    'matched_plus_not_matched_equals_total' => $run->matched_plus_not_matched_equals_total,
                ],
                'not_match_rows' => $run->not_match_rows,
                'match_rows' => $run->match_rows ?? [],
            ],
        ]);
    }

    public function importVerifiedList(Request $request, VerifiedListImporter $importer): JsonResponse
    {
        $request->validate(['file' => ['required', 'file']]);

        try {
            $count = $importer->import($request->file('file'));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['imported' => $count]);
    }
}
