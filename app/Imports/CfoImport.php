<?php

namespace App\Imports;

use App\Models\Cfo;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

HeadingRowFormatter::default('none');
class CfoImport implements withMapping, WithHeadingRow//,WithStartRow
{
//public function startRow(): int
//{
//    return 2;
//
//}
//    /**
//    * @param array $row
//    *
//    * @return \Illuminate\Database\Eloquent\Model|null
//    */
//
//     public function model(array $row)
//    {
//        return new Cfo([
//            'Work Order'=>$row['Work Order'],
//            'PORT'=>$row['PORT'],
//            'POS'=>$row['POS'],
//            'Team Install'=>$row['Team Install'],
//            'Create Time'=>$row['Create Time'],
//
//        ]);
//    }
//$sheet->mergeCells('A1:E1');

//    public function headingRow(): int
//    {
////        $sheet->
//        return 3;
//    }

    public function map($row): array{
        return [
            'Work Order'=>$row['Work Order'],
            'PORT'=>$row['PORT'],
          //  'New circuit ID'=>$row['New circuit ID'],
            'POS'=>$row['POS'],
            'Team Install'=>$row['Team Install'],
            'Create Time'=>$row['Create Time'],
        ];
    }

//    public function registerEvents(): array
//    {
//        return [
//            BeforeImport::class => function (BeforeImport $event) {
//                $worksheet = $event->getReader()->getSheetByIndex(2);
//
//                // Get all merged cells
//                $mergedCells = $worksheet->getMergeCells();
//dd($mergedCells);
//                foreach ($mergedCells as $range) {
//                    // Split range to get start cell (e.g., "A1:B1" -> "A1")
//                    $startCell = $worksheet->getCell(explode(':', $range)[0]);
//                    $headerValue = $startCell->getValue();
//
//                    echo "Merged Header: $headerValue in $range\n";
//                }
//            },
//        ];
//    }
}
