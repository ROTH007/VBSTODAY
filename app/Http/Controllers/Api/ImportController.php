<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InvalidCompanyHeaderException;
use App\Exports\MainSheet;
use App\Http\Controllers\Controller;
use App\Models\Import;
use App\Models\ImportRow;
use App\Services\BuilderImporter;
use App\Services\ImportMatcher;
use App\Services\ImportRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;

class ImportController extends Controller
{
    private const BUILDER_HEADER = ['id', 'name', 'active', 'date', 'infrastructure', 'jobs', 'category', 'installation_order'];

    public function __construct(
        private BuilderImporter $builderImporter,
        private ImportMatcher $importMatcher,
        private ImportRecorder $importRecorder,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $imports = Import::orderByDesc('id')->paginate($perPage);

        return response()->json([
            'data' => $imports->map(fn (Import $import) => $this->summarize($import))->values(),
            'meta' => [
                'page' => $imports->currentPage(),
                'per_page' => $imports->perPage(),
                'total' => $imports->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'builder' => ['required', 'file'],
            'cfo' => ['required', 'file'],
            'company' => ['required', 'in:cfocn,tct,adi,telecom'],
            'status' => ['nullable', 'in:Active,Terminated'],
        ]);

        $builderFile = $request->file('builder');
        $companyFile = $request->file('cfo');

        // Read both header rows before touching any Import class below:
        // instantiating one (e.g. BuilderImport, via replace()/match()) flips
        // a package-wide static default (HeadingRowFormatter) as a side
        // effect of autoloading, which would un-slugify a header read after it.
        $builderHeaderRow = (new HeadingRowImport())->toArray($builderFile)[0][0] ?? [];
        $companyHeaderRow = (new HeadingRowImport())->toArray($companyFile)[0][0] ?? [];

        if (! $this->builderHeaderIsValid($builderHeaderRow)) {
            return response()->json([
                'error' => 'invalid_builder_header',
                'message' => 'Builder file header does not match the expected columns.',
            ], 422);
        }

        $import = Import::create([
            'company' => $data['company'],
            'status_filter' => $data['status'] ?? null,
            'builder_filename' => $builderFile->getClientOriginalName(),
            'cfo_filename' => $companyFile->getClientOriginalName(),
            'status' => 'processing',
            'created_by' => $request->user()?->id,
        ]);

        $this->builderImporter->replace($builderFile);

        try {
            $result = $this->importMatcher->match($data['company'], $companyHeaderRow, $companyFile);
        } catch (InvalidCompanyHeaderException $e) {
            $this->importRecorder->recordFailure($import, $e->getMessage());

            return response()->json([
                'error' => 'invalid_company_header',
                'message' => $e->getMessage(),
            ], 422);
        }

        $import = $this->importRecorder->record($import, $result);

        return response()->json($this->summarize($import), 201);
    }

    public function show(Import $import): JsonResponse
    {
        return response()->json($this->summarize($import));
    }

    public function rows(Request $request, Import $import): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'in:match,not_match,bill'],
        ]);

        $perPage = (int) $request->input('per_page', 25);
        $rows = $import->rows()
            ->where('match_type', $request->input('type'))
            ->orderBy('id')
            ->paginate($perPage);

        return response()->json([
            'data' => $rows->map(fn (ImportRow $row) => $row->row_data)->values(),
            'meta' => [
                'page' => $rows->currentPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ],
        ]);
    }

    public function download(Import $import)
    {
        $match = $import->rows()->where('match_type', 'match')->orderBy('id')->get()->pluck('row_data')->all();
        $notMatch = $import->rows()->where('match_type', 'not_match')->orderBy('id')->get()->pluck('row_data')->all();
        $bill = $import->rows()->where('match_type', 'bill')->orderBy('id')->get()->pluck('row_data')->all();

        $companyHeader = ImportMatcher::COMPANY_HEADERS[$import->company] ?? [];
        $fileName = ImportMatcher::TITLES[$import->company].' '.($import->status_filter ?? '').' List';

        return Excel::download(new MainSheet($match, $bill, $notMatch, $companyHeader), trim($fileName).'.xlsx');
    }

    private function summarize(Import $import): array
    {
        return [
            'id' => $import->id,
            'company' => $import->company,
            'status' => $import->status,
            'status_filter' => $import->status_filter,
            'error_message' => $import->error_message,
            'counts' => [
                'match' => $import->match_count,
                'not_match' => $import->not_match_count,
                'bill' => $import->bill_count,
            ],
            'created_at' => $import->created_at,
        ];
    }

    private function builderHeaderIsValid(array $headerRow): bool
    {
        foreach (self::BUILDER_HEADER as $i => $expected) {
            if (($headerRow[$i] ?? null) !== $expected) {
                return false;
            }
        }

        return true;
    }
}
