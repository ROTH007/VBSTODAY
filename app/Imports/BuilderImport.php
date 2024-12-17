<?php

namespace App\Imports;

use App\Models\Builder;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

HeadingRowFormatter::default('none');
class BuilderImport implements ToModel,withHeadingRow
{
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
