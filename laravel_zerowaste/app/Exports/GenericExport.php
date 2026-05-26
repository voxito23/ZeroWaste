<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class GenericExport implements FromView, ShouldAutoSize, WithTitle, WithStyles, WithDrawings
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
        // Hacer la primera fila más alta para que quepa el logo
        $sheet->getRowDimension(1)->setRowHeight(60);
        
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
        ]);

        return [];
    }

    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('ZeroWaste Logo');
        $drawing->setDescription('ZeroWaste Logo');
        
        // Buscar el logo en diferentes rutas posibles
        $logoPath = null;
        $possiblePaths = [
            public_path('img/logo_texture.png'),
            base_path('../flask_zerowaste/static/img/logo_texture.png'),
            '/var/www/flask_zerowaste/static/img/logo_texture.png',
            '/opt/ZeroWaste/flask_zerowaste/static/img/logo_texture.png'
        ];
        
        foreach($possiblePaths as $p) {
            if(file_exists($p)) {
                $logoPath = $p;
                break;
            }
        }
        
        if ($logoPath) {
            $drawing->setPath($logoPath);
            $drawing->setHeight(50); // Ajustar altura del logo
            $drawing->setCoordinates('D1'); // Colocar en la columna central
            $drawing->setOffsetX(40); // Offset para centrar un poco más en la celda
            $drawing->setOffsetY(5);
            return [$drawing];
        }
        
        return [];
    }
}
