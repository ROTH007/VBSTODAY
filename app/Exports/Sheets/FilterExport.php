<?php

namespace App\Exports\Sheets;

use App\Models\Filter;
use Maatwebsite\Excel\Concerns\FromCollection;

class FilterExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Filter::all();
    }
}
