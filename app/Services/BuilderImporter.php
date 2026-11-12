<?php

namespace App\Services;

use App\Imports\BuilderImport;
use App\Models\Builder;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Always replaces the builders table with the freshly uploaded Builder
 * file, instead of only importing "if the table happens to be empty"
 * (which let a stale/aborted previous upload be silently reused).
 *
 * Not wrapped in a DB transaction: TRUNCATE implicitly commits on MySQL,
 * so it can't participate in one anyway.
 */
class BuilderImporter
{
    public function replace(UploadedFile $builderFile): void
    {
        Builder::truncate();
        Excel::import(new BuilderImport(), $builderFile);
    }
}
