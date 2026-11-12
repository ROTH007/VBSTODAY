<?php

namespace App\Imports;

use App\Imports\Concerns\ScopesNoneHeadingFormatter;
use App\Models\Tct;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMapping;
use function Laravel\Prompts\select;

class TctImport implements WithHeadingRow, WithMapping, WithEvents
{
    use ScopesNoneHeadingFormatter;

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
