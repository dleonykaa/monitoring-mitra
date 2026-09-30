<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Template alokasi ruta survei PAPI. Lembar "Alokasi" diisi admin lalu diimpor; lembar "Contoh"
 * hanya panduan. Mitra dikenali dari email akunnya, bukan dari nama.
 */
class AssignmentTemplateExport implements WithMultipleSheets
{
    public const HEADINGS = ['Kode Prov', 'Kode Kab', 'Kelurahan', 'Email', 'SLS', 'PPL', 'No Urut Ruta [max: 2 digit]'];

    /**
     * Nama kolom (huruf kecil, tanpa keterangan dalam kurung) yang wajib ada saat import.
     */
    public const REQUIRED_COLUMNS = ['kode prov', 'kode kab', 'kelurahan', 'email', 'sls', 'no urut ruta'];

    /**
     * @param  Collection<int, array{name: string, email: string}>  $exampleMitra
     * @param  Collection<int, string>  $villageNames
     */
    public function __construct(
        private readonly Collection $exampleMitra,
        private readonly Collection $villageNames,
    ) {}

    public function sheets(): array
    {
        $examples = [['', '', '', '', '', 'CONTOH PENGISIAN', '']];
        $village = strtoupper($this->villageNames->first() ?? 'PULAU TIDUNG');
        $mitra = $this->exampleMitra->isNotEmpty() ? $this->exampleMitra : collect([['name' => 'Nama Mitra', 'email' => 'mitra@gmail.com']]);

        foreach ($mitra->take(2)->values() as $mIndex => $person) {
            $sls = 'RT 00'.($mIndex + 1).' RW 001';
            for ($ruta = 1; $ruta <= 3; $ruta++) {
                $examples[] = ['31', '01', $village, $person['email'], $sls, $person['name'], $ruta];
            }
        }

        return [
            $this->sheet('Alokasi', [], 'Isi satu baris per ruta. Kelurahan: '.$this->villageNames->map(fn ($name) => strtoupper($name))->join(', ').'.'),
            $this->sheet('Contoh', $examples, 'Lembar ini hanya contoh dan tidak ikut diimpor.'),
        ];
    }

    /**
     * @param  list<list<mixed>>  $rows
     */
    private function sheet(string $title, array $rows, string $note): object
    {
        return new class($title, $rows, $note) implements FromArray, ShouldAutoSize, WithStyles, WithTitle
        {
            public function __construct(private readonly string $title, private readonly array $rows, private readonly string $note) {}

            public function array(): array
            {
                return [AssignmentTemplateExport::HEADINGS, ...$this->rows];
            }

            public function title(): string
            {
                return $this->title;
            }

            public function styles(Worksheet $sheet): array
            {
                $header = $sheet->getStyle('A1:G1');
                $header->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
                $header->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E26B0A');
                $sheet->getStyle('A1:G'.max(2, count($this->rows) + 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle('A:G')->getNumberFormat()->setFormatCode('@');
                $sheet->setCellValue('I1', $this->note);
                $sheet->getStyle('I1')->getFont()->setItalic(true)->getColor()->setRGB('64748B');

                if ($this->rows !== []) {
                    $sheet->getStyle('F2')->getFont()->setBold(true)->getColor()->setRGB('E26B0A');
                }

                return [];
            }
        };
    }
}
