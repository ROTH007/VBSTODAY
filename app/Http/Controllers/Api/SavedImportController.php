<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSavedImportRequest;
use App\Models\SavedImport;
use App\Services\AuditLogger;
use App\Services\SavedImportRecorder;
use App\Services\SavedImportRowReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SavedImportController extends Controller
{
    public function __construct(private AuditLogger $auditLogger, private SavedImportRowReader $rowReader)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);

        $query = SavedImport::query()->orderByDesc('id');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('label', 'LIKE', "%{$search}%")
                    ->orWhere('company', 'LIKE', "%{$search}%");
            });
        }

        $imports = $query->paginate($perPage);

        return response()->json([
            'data' => $imports->map(fn (SavedImport $i) => $this->summarize($i))->values(),
            'meta' => [
                'page' => $imports->currentPage(),
                'per_page' => $imports->perPage(),
                'total' => $imports->total(),
            ],
        ]);
    }

    public function show(SavedImport $savedImport): JsonResponse
    {
        return response()->json($this->summarize($savedImport));
    }

    public function update(UpdateSavedImportRequest $request, SavedImport $savedImport): JsonResponse
    {
        $oldLabel = $savedImport->label;
        $newLabel = $request->validated('label');

        $savedImport->update(['label' => $newLabel]);

        $this->auditLogger->record(
            action: 'update',
            model: 'SavedImport',
            modelId: $savedImport->id,
            changes: ['label' => [$oldLabel, $newLabel]],
            userId: $request->user()?->id,
        );

        return response()->json($this->summarize($savedImport));
    }

    public function destroy(Request $request, SavedImport $savedImport): JsonResponse
    {
        $savedImport->delete();

        $this->auditLogger->record(
            action: 'delete',
            model: 'SavedImport',
            modelId: $savedImport->id,
            changes: null,
            userId: $request->user()?->id,
        );

        return response()->json(['message' => 'Deleted.']);
    }

    public function rows(Request $request, SavedImport $savedImport): JsonResponse
    {
        $sheet = $request->input('sheet', 'not_match');

        if (! in_array($sheet, ['bill', 'match', 'not_match'], true)) {
            return response()->json(['message' => 'Invalid sheet. Use bill, match, or not_match.'], 422);
        }

        $perPage = (int) $request->input('per_page', 10);
        $page = (int) $request->input('page', 1);

        $result = $this->rowReader->read($savedImport, $sheet, $request->input('search'), $page, $perPage);

        return response()->json([
            'columns' => $result['columns'],
            'data' => $result['rows'],
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $result['total'],
            ],
        ]);
    }

    public function download(SavedImport $savedImport)
    {
        $path = app(SavedImportRecorder::class)->workbookPath($savedImport->id);

        if (! Storage::exists($path)) {
            return response()->json(['message' => 'Workbook file not found.'], 404);
        }

        return Storage::download($path, $savedImport->company.'_'.($savedImport->status ?? 'result').'_List.xlsx');
    }

    private function summarize(SavedImport $savedImport): array
    {
        return [
            'id' => $savedImport->id,
            'label' => $savedImport->label,
            'company' => $savedImport->company,
            'status' => $savedImport->status,
            'counts' => [
                'match' => $savedImport->match_count,
                'not_match' => $savedImport->not_match_count,
                'bill' => $savedImport->bill_count,
            ],
            'unmatched_breakdown' => [
                'other_infrastructure' => $savedImport->other_infrastructure_count,
                'not_in_builder' => $savedImport->not_in_builder_count,
            ],
            'created_at' => $savedImport->created_at,
            'updated_at' => $savedImport->updated_at,
        ];
    }
}
