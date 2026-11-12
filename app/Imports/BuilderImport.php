<?php

namespace App\Imports;

use App\Imports\Concerns\ScopesNoneHeadingFormatter;
use App\Models\Builder;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMapping;

class BuilderImport implements ToModel, WithHeadingRow, WithEvents
{
    use ScopesNoneHeadingFormatter;

//    /**
//    * @param array $row
//    *
//    * @return \Illuminate\Database\Eloquent\Model|null
//    */
    public function model(array $row): Builder
    {
        return new Builder([
            'active_id' =>$row['id'],
            'infrastructure'=>$row['infrastructure'],
            'jobs' => $row['jobs'],
            ]);
}


}
