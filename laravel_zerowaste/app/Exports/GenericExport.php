<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GenericExport implements FromView, ShouldAutoSize, WithTitle, WithStyles
{
    protected $data;
    
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('reporte_excel', $this->data);
    }

    public function title(): string
    {
        return ucfirst($this->data['tipo'] ?? 'Reporte');
    }

    public function styles(Worksheet $sheet)
    {
        // Style the header row
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
        ]);

        return [];
    }
}
