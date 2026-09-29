<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AssignmentTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles
{
    public const HEADINGS = [
        'Kode Prov',
        'Kode Kab',
        'Kecamatan',
        'Kelurahan',
        'Kode NKS',
        'SLS',
        'PPL',
        'No Urut Ruta',
    ];

    public function array(): array
    {
        return [
            ['31', '01', '', '', '', '', '', ''],
        ];
    }

    public function headings(): array
    {
        return self::HEADINGS;
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('A1:H2')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        return [];
    }
}
