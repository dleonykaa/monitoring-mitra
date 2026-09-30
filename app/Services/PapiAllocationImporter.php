<?php

namespace App\Services;

use App\Exports\AssignmentTemplateExport;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyEntry;
use App\Models\User;
use App\Models\Village;
use App\Notifications\MitraAssignedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Import alokasi ruta survei PAPI dari template Excel. Satu baris = satu ruta untuk mitra yang
 * dikenali dari email akunnya; ruta menjadi entri "Open" yang langsung bisa diisi mitra.
 * Impor selalu mengganti seluruh alokasi survei, dan ditolak bila mitra sudah mengisi sebagian ruta.
 */
class PapiAllocationImporter
{
    private const MAX_REPORTED_ERRORS = 10;

    /**
     * @return array{mitra: int, rows: int}
     *
     * @throws ValidationException
     */
    public function import(Survey $survey, UploadedFile $file): array
    {
        $rows = $this->parse($file, $survey);

        $newMitraIds = DB::transaction(function () use ($survey, $rows): Collection {
            // Mitra yang sudah dialokasikan sebelumnya tidak diberi notifikasi penugasan ulang.
            $existingMitraIds = $survey->assignments()->pluck('mitra_id');
            $survey->assignments()->delete();

            foreach ($rows->groupBy('mitra_id') as $mitraId => $mitraRows) {
                $assignment = SurveyAssignment::query()->firstOrNew(['survey_id' => $survey->id, 'mitra_id' => $mitraId]);
                $assignment->target = ($assignment->exists ? (int) $assignment->target : 0) + $mitraRows->count();
                $assignment->current_progress ??= 0;
                $assignment->save();

                $now = now();
                SurveyEntry::query()->insert($mitraRows->map(fn (array $row): array => [
                    'survey_id' => $survey->id,
                    'survey_assignment_id' => $assignment->id,
                    'district_id' => $row['district_id'],
                    'village_id' => $row['village_id'],
                    'sls' => $row['sls'],
                    'ppl' => $row['ppl'],
                    'no_urut_ruta' => $row['no_urut_ruta'],
                    'entry_status' => SurveyEntry::STATUS_OPEN,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            }

            return $rows->pluck('mitra_id')->unique()->diff($existingMitraIds)->values();
        });

        $survey->recalculateTarget();

        // Survei Draft belum terlihat mitra; notifikasinya dikirim saat admin menjalankan survei.
        if ($survey->status === 'Berjalan') {
            User::query()->whereIn('id', $newMitraIds)->get()
                ->each(fn (User $mitra) => $mitra->notify(new MitraAssignedNotification($survey)));
        }

        return ['mitra' => $rows->pluck('mitra_id')->unique()->count(), 'rows' => $rows->count()];
    }

    /**
     * @return Collection<int, array{district_id: int, village_id: int, sls: string, ppl: string, mitra_id: int, no_urut_ruta: string}>
     *
     * @throws ValidationException
     */
    public function parse(UploadedFile $file, Survey $survey): Collection
    {
        if ($survey->entries()->where('entry_status', '!=', SurveyEntry::STATUS_OPEN)->exists()) {
            throw ValidationException::withMessages(['file' => [
                'Alokasi tidak bisa diganti lewat impor karena mitra sudah mengisi sebagian ruta. Tambahkan ruta baru lewat "Tambah manual".',
            ]]);
        }

        $sheet = Excel::toArray([], $file)[0] ?? [];
        [$headerIndex, $columns] = $this->locateHeader($sheet);

        $villages = Village::query()->get()->groupBy(fn (Village $village) => $this->normalize($village->name));
        $mitraByEmail = User::query()->role('mitra')->where('is_active', true)->get()
            ->keyBy(fn (User $user) => strtolower($user->email));

        $errors = [];
        $parsed = collect();
        $seenKeys = [];

        foreach (array_slice($sheet, $headerIndex + 1, null, true) as $index => $row) {
            $rowNumber = $index + 1;
            $cell = fn (string $column): string => $this->cellText($row[$columns[$column]] ?? '');
            $joined = implode(' ', array_map(fn ($value) => $this->cellText($value), $row));

            if (trim($joined) === '' || str_contains(strtoupper($joined), 'CONTOH PENGISIAN')) {
                continue;
            }

            $rowErrors = [];
            $kodeProv = $cell('kode prov');
            $kodeKab = str_pad($cell('kode kab'), 2, '0', STR_PAD_LEFT);
            $kelurahan = $cell('kelurahan');
            $email = strtolower($cell('email'));
            $sls = preg_replace('/\s+/', ' ', $cell('sls'));
            $noUrutRuta = $cell('no urut ruta');

            if ($kodeProv !== '31') {
                $rowErrors[] = "Kode Prov harus 31 (terisi '{$kodeProv}')";
            }
            if ($kodeKab !== '01') {
                $rowErrors[] = "Kode Kab harus 01 (terisi '{$cell('kode kab')}')";
            }

            $villageMatches = $villages->get($this->normalize($kelurahan), collect());
            $village = $villageMatches->count() === 1 ? $villageMatches->first() : null;
            if (! $village) {
                $rowErrors[] = $kelurahan === '' ? 'Kelurahan wajib diisi' : "Kelurahan '{$kelurahan}' tidak terdaftar";
            }

            $mitra = $mitraByEmail->get($email);
            if (! $mitra) {
                $rowErrors[] = $email === '' ? 'Email mitra wajib diisi' : "Email '{$email}' bukan akun mitra aktif";
            }

            if ($sls === '') {
                $rowErrors[] = 'SLS wajib diisi';
            }

            if (! preg_match('/^\d{1,2}$/', $noUrutRuta) || (int) $noUrutRuta < 1) {
                $rowErrors[] = $noUrutRuta === '' ? 'No Urut Ruta wajib diisi' : "No Urut Ruta '{$noUrutRuta}' harus angka 1–99";
            }

            if ($rowErrors === [] && $village && $mitra) {
                $key = $this->rutaKey($village->id, $sls, $noUrutRuta);
                if (isset($seenKeys[$key])) {
                    $rowErrors[] = "ruta ganda dengan baris {$seenKeys[$key]} ({$kelurahan}, {$sls}, no {$noUrutRuta})";
                } else {
                    $seenKeys[$key] = $rowNumber;
                    $parsed->push([
                        'district_id' => $village->district_id,
                        'village_id' => $village->id,
                        'sls' => $sls,
                        'ppl' => $mitra->name,
                        'mitra_id' => $mitra->id,
                        'no_urut_ruta' => (string) (int) $noUrutRuta,
                    ]);
                }
            }

            foreach ($rowErrors as $message) {
                $errors[] = "Baris {$rowNumber}: {$message}.";
            }
            if (count($errors) >= self::MAX_REPORTED_ERRORS) {
                $errors[] = 'Periksa juga baris-baris berikutnya.';
                break;
            }
        }

        if ($errors === [] && $parsed->isEmpty()) {
            $errors[] = 'Tidak ada baris alokasi yang bisa diproses. Isi baris di bawah judul kolom.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['file' => ['Upload dibatalkan, tidak ada data yang disimpan. '.implode(' ', $errors)]]);
        }

        return $parsed;
    }

    /**
     * Cari baris judul (boleh bukan baris pertama) lalu petakan nama kolom ke indeksnya.
     *
     * @param  array<int, array<int, mixed>>  $sheet
     * @return array{0: int, 1: array<string, int>}
     *
     * @throws ValidationException
     */
    private function locateHeader(array $sheet): array
    {
        foreach (array_slice($sheet, 0, 10, true) as $index => $row) {
            $columns = [];
            foreach ($row as $position => $value) {
                $name = $this->columnName($this->cellText($value));
                if ($name !== '' && ! isset($columns[$name])) {
                    $columns[$name] = $position;
                }
            }

            $missing = array_diff(AssignmentTemplateExport::REQUIRED_COLUMNS, array_keys($columns));
            if ($missing === []) {
                return [$index, $columns];
            }
        }

        throw ValidationException::withMessages(['file' => [
            'Format template tidak dikenali. Pastikan ada kolom: '.implode(', ', AssignmentTemplateExport::HEADINGS).'. Unduh template terbaru bila ragu.',
        ]]);
    }

    /**
     * Samakan variasi judul kolom: "kode prop" / "Kode Provinsi" -> "kode prov",
     * "No. Urut Ruta [max: 2 digit]" -> "no urut ruta", "Email Mitra" -> "email".
     */
    private function columnName(string $header): string
    {
        $name = $this->normalize(str_replace('.', ' ', preg_replace('/[\[(].*$/', '', $header)));

        return match (true) {
            (bool) preg_match('/^kode (prop|prov|provinsi)$/', $name) => 'kode prov',
            (bool) preg_match('/^kode (kab|kabupaten|kab\/kota)$/', $name) => 'kode kab',
            (bool) preg_match('/^(kelurahan|desa|desa\/kelurahan|kelurahan\/desa)$/', $name) => 'kelurahan',
            (bool) preg_match('/^(email|email mitra|e-mail)$/', $name) => 'email',
            (bool) preg_match('/^no urut ruta$/', $name) => 'no urut ruta',
            default => $name,
        };
    }

    private function cellText(mixed $value): string
    {
        if (is_float($value) && floor($value) === $value) {
            $value = (int) $value;
        }

        return trim((string) $value);
    }

    private function normalize(string $value): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $value)));
    }

    private function rutaKey(?int $villageId, ?string $sls, ?string $noUrutRuta): string
    {
        return $villageId.'|'.$this->normalize((string) $sls).'|'.(int) $noUrutRuta;
    }
}
