<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * A minimal, mapping-free import used only by the new infrastructure
 * selector feature to read a file's rows as plain associative arrays
 * keyed by its own header row. Deliberately does not implement ToModel
 * or WithMapping, so it never touches any Eloquent model or the
 * `builders` table.
 */
class RawRowsImport implements WithHeadingRow
{
}
