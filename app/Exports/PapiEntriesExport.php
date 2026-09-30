<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Ekspor Data Entri PAPI. Satu baris per ruta; kolom variabel mengikuti form isian survei.
 */
class PapiEntriesExport implements FromCollection, ShouldAutoSize, WithCustomCsvSettings, WithHeadings, WithStyles, WithTitle
{
    /**
     * Kolom identitas sebelum kolom variabel (No, Status, Mitra, Email, Kecamatan, Kelurahan, SLS, No Urut Ruta).
     */
    private const IDENTITY_COLUMNS = 8;

    /**
     * @param  Collection<int, list<mixed>>  $rows
     * @param  list<string>  $headings
     */
    public function __construct(
        private readonly Collection $rows,
        private readonly array $headings,
        private readonly string $surveyTitle,
        private readonly int $variableCount,
    ) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        // Nama sheet Excel maksimal 31 karakter dan tanpa karakter khusus.
        return mb_substr(preg_replace('/[\\\\\/?*\[\]:]/', ' ', 'Entri '.$this->surveyTitle), 0, 31);
    }

    /**
     * BOM UTF-8 agar Excel membaca huruf Indonesia dengan benar saat membuka CSV.
     *
     * @return array<string, mixed>
     */
    public function getCsvSettings(): array
    {
        return ['use_bom' => true, 'delimiter' => ','];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastColumn = Coordinate::stringFromColumnIndex(count($this->headings));
        $lastRow = max(1, $this->rows->count() + 1);

        $header = $sheet->getStyle('A1:'.$lastColumn.'1');
        $header->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $header->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0B2447');
        $header->getAlignment()->setVertical('center')->setWrapText(true);

        if ($this->variableCount > 0) {
            $firstVariable = Coordinate::stringFromColumnIndex(self::IDENTITY_COLUMNS + 1);
            $lastVariable = Coordinate::stringFromColumnIndex(self::IDENTITY_COLUMNS + $this->variableCount);
            $sheet->getStyle($firstVariable.'1:'.$lastVariable.'1')->getFill()->getStartColor()->setRGB('1D4ED8');
        }

        $sheet->getStyle('A1:'.$lastColumn.$lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->freezePane('C2');
        $sheet->setAutoFilter('A1:'.$lastColumn.$lastRow);

        return [];
    }
}
