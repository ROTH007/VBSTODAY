<?php

namespace App\Imports;

use App\Models\Tct;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use function Laravel\Prompts\select;

HeadingRowFormatter::default('none');
class TctImport implements WithHeadingRow, WithMapping
{
//    /**
//    * @param array $row
//    *
//     * @return \Illuminate\Database\Eloquent\Model|null
//     */
//    public function model(array $row)
//    {
//        return new Tct([
//            'TCT CID'=>$row['TCT CID'],
//            'TCT SID'=>$row['TCT SID'],
//            'New circuit ID'=>$row['New circuit ID'],
////            'CONTRACT TERM'=>$row['CONTRACT TERM'],//CONTRACT TERM
//            'Status'=>$row['Status'],
//            'Total NRC'=>$row['Total NRC'],
//        ]);
//    }
    public function map($row): array{
        return [
            'TCT CID'=>$row['TCT CID'],
            'TCT SID'=>$row['TCT SID'],
            'New circuit ID'=>$row['New circuit ID'],
//            'CONTRACT TERM'=>$row['CONTRACT TERM'],
            'Status'=>$row['Status'],
            'Total NRC'=>$row['Total NRC'],
        ];
    }
}
