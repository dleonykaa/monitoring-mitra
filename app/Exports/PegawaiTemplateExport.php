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
 * Template penugasan pegawai penanggung jawab survei. Lembar "Pegawai" diisi admin lalu diimpor;
 * lembar "Contoh" hanya panduan. Pegawai dikenali dari email akunnya, kolom nama hanya penanda.
 */
class PegawaiTemplateExport implements WithMultipleSheets
{
    public const HEADINGS = ['Email', 'Nama Pegawai (opsional)'];

    /**
     * @param  Collection<int, array{name: string, email: string}>  $examplePegawai
     * @param  list<array{0: string, 1: string}>  $rows  Isi lembar "Pegawai"; kosong untuk template.
     */
    public function __construct(
        private readonly Collection $examplePegawai,
        private readonly array $rows = [],
    ) {}

    public function sheets(): array
    {
        $pegawai = $this->examplePegawai->isNotEmpty()
            ? $this->examplePegawai
            : collect([['name' => 'Nama Pegawai', 'email' => 'pegawai@bps.go.id']]);
        $examples = [['CONTOH PENGISIAN', ''], ...$pegawai->take(3)->map(fn (array $person): array => [$person['email'], $person['name']])->all()];

        return [
            $this->sheet('Pegawai', $this->rows, 'Isi satu baris per pegawai. Email harus email akun pegawai BPS aktif di SIMPROCA.'),
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
                return [PegawaiTemplateExport::HEADINGS, ...$this->rows];
            }

            public function title(): string
            {
                return $this->title;
            }

            public function styles(Worksheet $sheet): array
            {
                $header = $sheet->getStyle('A1:B1');
                $header->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
                $header->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E26B0A');
                $sheet->getStyle('A1:B'.max(2, count($this->rows) + 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle('A:B')->getNumberFormat()->setFormatCode('@');
                $sheet->setCellValue('D1', $this->note);
                $sheet->getStyle('D1')->getFont()->setItalic(true)->getColor()->setRGB('64748B');

                if (($this->rows[0][0] ?? '') === 'CONTOH PENGISIAN') {
                    $sheet->getStyle('A2')->getFont()->setBold(true)->getColor()->setRGB('E26B0A');
                }

                return [];
            }
        };
    }
}
