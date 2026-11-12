<?php

namespace App\Imports;

use App\Imports\Concerns\ScopesNoneHeadingFormatter;
use App\Models\Adi;
use App\Models\Tct;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMapping;

class AdiImport implements WithHeadingRow, WithMapping, WithEvents
{
    use ScopesNoneHeadingFormatter;

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
//    public function model(array $row)
//    {
//        return new Adi([
//            //
//        ]);
//    }
public function map( $row): array{
    return [
       "AID" => $row["AID"],
        "SID" => $row["SID"],
        "Customer Name"=>$row["Customer Name"],
        "Qty"=>$row["Qty"],
        "Amount"=>$row["Amount"],
        "VAT"=>$row["VAT"],
        "Total Amount"=>$row["Total Amount"],
        "Invoice Description"=>$row["Invoice Description"],
        "Date Start"=>$row["Date Start"],
        "Date To"=>$row["Date To"],
    ];
}
}
