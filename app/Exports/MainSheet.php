<?php

namespace App\Exports;

use App\Exports\Sheets\NotMatchSheet;
use App\Models\Cfo;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Sheet;

class MainSheet implements withMultipleSheets
{
    protected array $duplicate= [];
    protected array $billFilter= [];
    private array $other=[];
    private array $companyHeader=[];

    public function __construct(array $duplicate, array $billFilter, array $other, array $companyHeader)
    {
        $this->duplicate= $duplicate;
        $this->billFilter= $billFilter;
        $this->other= $other;
        $this->companyHeader = $companyHeader;
    }
    public function sheets(): array
    {
        return [
           'Check'=> new Sheets\BillSheet($this->billFilter),
           'Pay'=>  new Sheets\MatchSheet($this->duplicate, $this->companyHeader),
            'Other'=> new NotMatchSheet($this->other, $this->companyHeader),
        ];
    }
}
