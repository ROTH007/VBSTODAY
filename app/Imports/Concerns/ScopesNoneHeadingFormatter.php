<?php

namespace App\Imports\Concerns;

use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Events\ImportFailed;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

/**
 * This import's rows are mapped by literal header text (e.g. "Work Order"),
 * not the slugified default ("work_order"). HeadingRowFormatter is a
 * package-wide static, so setting it permanently (e.g. a top-level
 * statement in the import class file) would leak into every later import
 * in the same PHP process — including this app's own header-validation
 * reads on a subsequent, unrelated request. Scoping it to exactly this
 * import's own read, via Maatwebsite's own event hooks, avoids that.
 */
trait ScopesNoneHeadingFormatter
{
    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function (BeforeImport $event) {
                HeadingRowFormatter::default('none');
            },
            AfterImport::class => function (AfterImport $event) {
                HeadingRowFormatter::reset();
            },
            ImportFailed::class => function (ImportFailed $event) {
                HeadingRowFormatter::reset();
            },
        ];
    }
}
