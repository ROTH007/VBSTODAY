<?php

namespace Tests\Feature;

use App\Imports\BuilderImport;
use App\Imports\CfoImport;
use App\Models\Builder;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Covers the fix to the HeadingRowFormatter leak: BuilderImport/CfoImport
 * (and the other three Import classes) used to set a package-wide static
 * formatter permanently, the first time any of them was used in a PHP
 * process. Since this app runs under a long-running `php artisan serve`
 * process (not one process per request), that broke every CFOCN import
 * after the first one handled by the same server. All fixtures here are
 * synthetic, generated at runtime.
 */
class HeadingRowFormatterIsolationTest extends TestCase
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

    private function cfocnCompanyXlsx(array $rows): UploadedFile
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

    private function postCfocnImport(UploadedFile $builder, UploadedFile $company)
    {
        return $this->post('/importExecute', [
            'builder' => $builder,
            'cfo' => $company,
            'company' => 'cfocn',
            'status' => 'active',
        ]);
    }

    public function test_two_sequential_full_cfocn_imports_both_succeed_in_the_same_process(): void
    {
        $first = $this->postCfocnImport(
            $this->builderXlsx([[1, 'A', 'Y', '2026-01-01', 'CFOCN', 'ISOJOB1', 'Business', 1]]),
            $this->cfocnCompanyXlsx([['ISOJOB1', 'P1', 'POS1', 'T1', '2026-01-01']])
        );
        $first->assertOk();
        $this->assertSame(1, Builder::count());
        $this->assertNotNull(Builder::where('jobs', 'ISOJOB1')->first());

        // A second, independent, fully successful CFOCN import in the
        // SAME PHP process (no server restart) — this is exactly what
        // used to throw "Undefined array key \"Work Order\"" in
        // CfoImport before the fix.
        $second = $this->postCfocnImport(
            $this->builderXlsx([[2, 'B', 'Y', '2026-01-01', 'CFOCN', 'ISOJOB2', 'Business', 2]]),
            $this->cfocnCompanyXlsx([['ISOJOB2', 'P2', 'POS2', 'T2', '2026-01-01']])
        );
        $second->assertOk();
        $this->assertSame(1, Builder::count());
        $this->assertNull(Builder::where('jobs', 'ISOJOB1')->first());
        $this->assertNotNull(Builder::where('jobs', 'ISOJOB2')->first());
    }

    public function test_builderimport_then_header_validation_does_not_leak_the_none_formatter(): void
    {
        // Use BuilderImport first (it used to flip the global formatter to
        // 'none' permanently on first use).
        $builderFile = $this->builderXlsx([[1, 'A', 'Y', '2026-01-01', 'CFOCN', 'ISOJOB1', 'Business', 1]]);
        Excel::toArray(new BuilderImport(), $builderFile);

        // A later, unrelated header read (as the controller does for
        // validation, before touching any Import class) must still see
        // the configured default ('slug'), not the leaked 'none'.
        $companyFile = $this->cfocnCompanyXlsx([['ISOJOB1', 'P1', 'POS1', 'T1', '2026-01-01']]);
        $header = (new HeadingRowImport())->toArray($companyFile)[0][0] ?? [];

        $this->assertSame('work_order', $header[0] ?? null);
        $this->assertSame('port', $header[1] ?? null);
    }

    public function test_cfoimport_then_header_validation_does_not_leak_the_none_formatter(): void
    {
        // Reverse order: CfoImport used first, to prove neither import
        // class leaks regardless of which one happens to load first in a
        // shared PHP process.
        $companyFile = $this->cfocnCompanyXlsx([['ISOJOB1', 'P1', 'POS1', 'T1', '2026-01-01']]);
        Excel::toArray(new CfoImport(), $companyFile);

        $builderFile = $this->builderXlsx([[1, 'A', 'Y', '2026-01-01', 'CFOCN', 'ISOJOB1', 'Business', 1]]);
        $header = (new HeadingRowImport())->toArray($builderFile)[0][0] ?? [];

        $this->assertSame('id', $header[0] ?? null);
        $this->assertSame('name', $header[1] ?? null);
    }
}
