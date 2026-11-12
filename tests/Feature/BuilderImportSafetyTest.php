<?php

namespace Tests\Feature;

use App\Models\Builder;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Covers the builder-loading fix in ImportFileController::verifyData(): a
 * failed builder import must never leave the `builders` table empty or
 * partially filled, and the previous valid data must survive untouched.
 * All fixtures here are generated at runtime (synthetic data only) — never
 * real customer data, never committed binary files.
 */
class BuilderImportSafetyTest extends TestCase
{
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }

        Builder::query()->delete();

        parent::tearDown();
    }

    private function builderXlsx(array $rows): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'builder_').'.xlsx';
        $this->tempFiles[] = $path;

        $sheet = new Spreadsheet();
        $ws = $sheet->getActiveSheet();
        $ws->fromArray(['id', 'name', 'active', 'date', 'infrastructure', 'jobs', 'category', 'installation_order'], null, 'A1');
        $ws->fromArray($rows, null, 'A2');
        (new Xlsx($sheet))->save($path);

        return new UploadedFile($path, 'builder.xlsx', null, null, true);
    }

    private function cfocnCompanyXlsx(array $rows = [['WO1', 'SUB1', 'POS1', 'TEAM1', '2026-01-01']]): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'company_').'.xlsx';
        $this->tempFiles[] = $path;

        $sheet = new Spreadsheet();
        $ws = $sheet->getActiveSheet();
        $ws->fromArray(['Work Order', 'PORT', 'POS', 'Team Install', 'Create Time'], null, 'A1');
        $ws->fromArray($rows, null, 'A2');
        (new Xlsx($sheet))->save($path);

        return new UploadedFile($path, 'company.xlsx', null, null, true);
    }

    private function postImport(UploadedFile $builder, UploadedFile $company)
    {
        // BuilderImport flips Maatwebsite's global static heading-row
        // formatter to 'none' the first time it is instantiated, and it
        // stays flipped for the rest of the PHP process. In production
        // every HTTP request is a fresh process, so this never shows up —
        // but within one PHPUnit process, a second sequential request in
        // the same test would otherwise see the flipped formatter. Reset
        // it before each call so these tests reflect real request isolation.
        HeadingRowFormatter::default('slug');

        return $this->post('/importExecute', [
            'builder' => $builder,
            'cfo' => $company,
            'company' => 'cfocn',
            'status' => 'active',
        ]);
    }

    public function test_successful_replacement_import_replaces_builders_atomically(): void
    {
        Builder::create(['active_id' => 'OLD1', 'infrastructure' => 'CFOCN', 'jobs' => 'OLDJOB']);

        $response = $this->postImport(
            $this->builderXlsx([
                [1, 'A', 'Y', '2026-01-01', 'CFOCN', 'NEWJOB1', 'Business', 1],
                [2, 'B', 'Y', '2026-01-01', 'CFOCN', 'NEWJOB2', 'Business', 2],
            ]),
            $this->cfocnCompanyXlsx()
        );

        $response->assertOk();
        $this->assertSame(2, Builder::count());
        $this->assertNull(Builder::where('active_id', 'OLD1')->first());
        $this->assertNotNull(Builder::where('jobs', 'NEWJOB1')->first());
        $this->assertNotNull(Builder::where('jobs', 'NEWJOB2')->first());
    }

    public function test_blank_id_row_is_rejected_and_builders_left_unchanged(): void
    {
        Builder::create(['active_id' => 'PREV1', 'infrastructure' => 'CFOCN', 'jobs' => 'PREVJOB']);

        $response = $this->postImport(
            $this->builderXlsx([
                [1, 'A', 'Y', '2026-01-01', 'CFOCN', 'NEWJOB1', 'Business', 1],
                ['', 'B', 'Y', '2026-01-01', 'CFOCN', 'NEWJOB2', 'Business', 2], // blank id
            ]),
            $this->cfocnCompanyXlsx()
        );

        $response->assertRedirect('/');
        $response->assertSessionHas('incorrectBuilder');

        $this->assertSame(1, Builder::count());
        $this->assertSame('PREVJOB', Builder::first()->jobs);
    }

    public function test_blank_jobs_row_is_rejected_and_builders_left_unchanged(): void
    {
        Builder::create(['active_id' => 'PREV1', 'infrastructure' => 'CFOCN', 'jobs' => 'PREVJOB']);

        $response = $this->postImport(
            $this->builderXlsx([
                [1, 'A', 'Y', '2026-01-01', 'CFOCN', 'NEWJOB1', 'Business', 1],
                [2, 'B', 'Y', '2026-01-01', 'CFOCN', '', 'Business', 2], // blank jobs
            ]),
            $this->cfocnCompanyXlsx()
        );

        $response->assertRedirect('/');
        $response->assertSessionHas('incorrectBuilder');

        $this->assertSame(1, Builder::count());
        $this->assertSame('PREVJOB', Builder::first()->jobs);
    }

    public function test_import_failure_after_some_rows_are_valid_rolls_back_and_preserves_previous_data(): void
    {
        Builder::create(['active_id' => 'PREV1', 'infrastructure' => 'CFOCN', 'jobs' => 'PREVJOB']);

        // Both id and jobs are non-blank here (passes the pre-check), but
        // the third row's `jobs` value exceeds the varchar(191) column
        // length, which MySQL rejects under strict mode — a realistic,
        // unexpected mid-import DB failure that pre-validation cannot
        // catch, proving the transactional rollback itself.
        $response = $this->postImport(
            $this->builderXlsx([
                [1, 'A', 'Y', '2026-01-01', 'CFOCN', 'NEWJOB1', 'Business', 1],
                [2, 'B', 'Y', '2026-01-01', 'CFOCN', 'NEWJOB2', 'Business', 2],
                [3, 'C', 'Y', '2026-01-01', 'CFOCN', str_repeat('X', 300), 'Business', 3],
            ]),
            $this->cfocnCompanyXlsx()
        );

        $response->assertRedirect('/');
        $response->assertSessionHas('incorrectBuilder');

        // Rows 1 and 2 must NOT have survived despite being individually
        // valid — the whole replacement rolled back together.
        $this->assertSame(1, Builder::count());
        $this->assertSame('PREVJOB', Builder::first()->jobs);
        $this->assertNull(Builder::where('jobs', 'NEWJOB1')->first());
        $this->assertNull(Builder::where('jobs', 'NEWJOB2')->first());
    }

    // "Retry after a failed import succeeds, and the previous valid data
    // remains unchanged until a valid retry replaces it" is proven by this
    // file's two tests together, not as one chained test:
    //   - test_import_failure_after_some_rows_are_valid_rolls_back_and_preserves_previous_data()
    //     proves a failure leaves the previous data completely intact.
    //   - test_successful_replacement_import_replaces_builders_atomically()
    //     proves a valid import (exactly what a retry resubmits) succeeds
    //     and fully replaces the data.
    // A single test chaining two real /importExecute requests in one
    // PHPUnit process was deliberately not written: a second request
    // reaching the CFOCN company-matching step in the SAME PHP process
    // hits an unrelated, pre-existing Maatwebsite quirk — a global static
    // heading-row formatter (HeadingRowFormatter) that BuilderImport and
    // CfoImport each flip exactly once, the first time either class is
    // used, for the rest of that process's lifetime. Confirmed separately
    // against the live port-8001 server: it causes any CFOCN import after
    // the first one handled by the same long-running `php artisan serve`
    // process to fail with "Undefined array key \"Work Order\"" in
    // CfoImport.php. This is out of scope for the current fix and is
    // reported separately rather than worked around here.
}
