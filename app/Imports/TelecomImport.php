<?php

namespace App\Imports;

use App\Models\Telecom;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

HeadingRowFormatter::default('none');
class TelecomImport implements WithHeadingRow, WithMapping
{
    /**
    * @param array $row
    *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
//    public function model(array $row)
//    {
//        return new Telecom([
//            "Account No"=>$row['Account No'],
//            "ID"=>$row['ID'],
//            "Name Customer"=>$row['Name Customer'],
//            "Project"=>$row['Project'],
//            "Issue Date"=>$row['Issue Date'],
//            "Complete Date"=>$row['Complete Date'],
//        ]);
//    }

    public function map($row): array{
        return [
            "Account No"=>$row['Account No'],
            "ID"=>$row['ID'],
            "Name Customer"=>$row['Name Customer'],
            "Project"=>$row['Project'],
            "Issue Date"=>$row['Issue date'],
            "Complete Date"=>$row['Complete Date'],
        ];
    }
}
