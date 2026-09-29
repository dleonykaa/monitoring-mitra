<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EntriesExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles
{
    public function __construct(
        private readonly Collection $rows,
        private readonly array $headings,
    ) {}

    public function collection(): Collection
    {
        return $this->rows->map(fn (array $row): array => array_values($row));
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastColumn = $sheet->getHighestColumn(1);
        $sheet->getStyle('A1:'.$lastColumn.'1')->getFont()->setBold(true);
        $sheet->getStyle('A1:'.$lastColumn.'1')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        return [];
    }
}
