<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class NotMatchSheet implements FromArray, WithHeadings, WithTitle
{
    /**
    * @return \Illuminate\Support\Collection
    */
    protected array $data;
    protected array $companyName;

    public function __construct(array $data, array $companyName){
        $this->data = $data;
        $this->companyName = $companyName;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return $this->companyName;
    }

    public function title(): string
    {
        return 'Not match';
    }
}
