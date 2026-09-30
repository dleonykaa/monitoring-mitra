<?php

namespace App\Services;

use App\Models\FasihImport;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use SplFileObject;

/**
 * Membaca file CSV hasil scraping progres FASIH dan menyimpannya sebagai satu snapshot import.
 */
class FasihProgressImporter
{
    /**
     * @var list<string>
     */
    public const REQUIRED_COLUMNS = ['email', 'regionCode', 'totalRegion', 'statusBreakdown'];

    private const MAX_REPORTED_ERRORS = 10;

    /**
     * @throws ValidationException
     */
    public function import(UploadedFile $file, ?User $importedBy, Survey $survey): FasihImport
    {
        $parsedRows = $this->parse($file->getRealPath());

        $fasihImport = DB::transaction(function () use ($file, $importedBy, $parsedRows, $survey): FasihImport {
            $fasihImport = FasihImport::query()->create([
                'survey_id' => $survey->id,
                'user_id' => $importedBy?->id,
                'file_name' => $file->getClientOriginalName(),
                'row_count' => count($parsedRows),
            ]);

            $now = now();
            $records = array_map(fn (array $row): array => [
                ...$row,
                'fasih_import_id' => $fasihImport->id,
                'status_breakdown' => json_encode($row['status_breakdown']),
                'created_at' => $now,
                'updated_at' => $now,
            ], $parsedRows);

            foreach (array_chunk($records, 500) as $chunk) {
                DB::table('fasih_progress_rows')->insert($chunk);
            }

            return $fasihImport;
        });

        $survey->recalculateTarget();

        return $fasihImport;
    }

    /**
     * Kolom namaPetugas bersifat opsional; bila ada, isinya dipakai sebagai nama pencacah.
     *
     * @return list<array{user_id: int|null, fasih_user_id: string|null, username: string|null, pencacah_name: string|null, email: string, region_code: string, district_code: string, village_code: string, sls_code: string, total_region: int, open_count: int, draft_count: int, submit_count: int, other_count: int, status_breakdown: array<string, int>}>
     *
     * @throws ValidationException
     */
    public function parse(string $path): array
    {
        $csv = new SplFileObject($path);
        $csv->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD | SplFileObject::DROP_NEW_LINE);

        $columnIndexes = null;
        $errors = [];
        $rows = [];

        foreach ($csv as $lineIndex => $cells) {
            if (! is_array($cells) || $cells === [null]) {
                continue;
            }

            if ($columnIndexes === null) {
                $columnIndexes = $this->resolveColumnIndexes($cells);

                continue;
            }

            $rowNumber = $lineIndex + 1;
            $value = fn (string $column): string => trim((string) ($cells[$columnIndexes[strtolower($column)] ?? -1] ?? ''));

            $email = strtolower($value('email'));
            $regionCode = $this->cleanRegionCode($value('regionCode'));
            $totalRegion = $value('totalRegion');

            if ($email === '') {
                $errors[] = "Baris {$rowNumber}: kolom email kosong.";
            }
            if (strlen($regionCode) !== 16) {
                $errors[] = "Baris {$rowNumber}: regionCode '{$value('regionCode')}' harus 16 digit.";
            }
            if (! ctype_digit($totalRegion)) {
                $errors[] = "Baris {$rowNumber}: totalRegion '{$totalRegion}' bukan angka.";
            }

            $statusCounts = $this->parseStatusBreakdown($value('statusBreakdown'));
            if ($statusCounts === null) {
                $errors[] = "Baris {$rowNumber}: format statusBreakdown tidak dikenali.";
            }

            if (count($errors) >= self::MAX_REPORTED_ERRORS) {
                break;
            }
            if ($errors !== []) {
                continue;
            }

            $groupedCounts = $this->groupStatusCounts($statusCounts);
            $total = (int) $totalRegion;

            $rows[] = [
                'user_id' => null,
                'fasih_user_id' => $value('userId') ?: null,
                'username' => $value('username') ?: null,
                'pencacah_name' => $value('namaPetugas') ?: ($value('namaPencacah') ?: null),
                'email' => $email,
                'region_code' => $regionCode,
                'district_code' => substr($regionCode, 0, 7),
                'village_code' => substr($regionCode, 0, 10),
                'sls_code' => substr($regionCode, 0, 14),
                'total_region' => $total,
                'open_count' => $groupedCounts['open'],
                'draft_count' => $groupedCounts['draft'],
                'submit_count' => $groupedCounts['submit'],
                'other_count' => max(0, $total - $groupedCounts['open'] - $groupedCounts['draft'] - $groupedCounts['submit']),
                'status_breakdown' => $statusCounts,
            ];
        }

        if ($columnIndexes === null) {
            throw ValidationException::withMessages(['file' => 'File kosong atau tidak dapat dibaca sebagai CSV.']);
        }
        if ($errors !== []) {
            throw ValidationException::withMessages(['file' => array_slice($errors, 0, self::MAX_REPORTED_ERRORS)]);
        }
        if ($rows === []) {
            throw ValidationException::withMessages(['file' => 'File tidak berisi baris data.']);
        }

        return $this->attachUserIds($rows);
    }

    /**
     * @param  array<int, string|null>  $headerCells
     * @return array<string, int>
     *
     * @throws ValidationException
     */
    private function resolveColumnIndexes(array $headerCells): array
    {
        $indexes = [];
        foreach ($headerCells as $index => $cell) {
            $name = strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $cell)));
            $indexes[$name] ??= $index;
        }

        $missing = array_filter(self::REQUIRED_COLUMNS, fn (string $column): bool => ! array_key_exists(strtolower($column), $indexes));
        if ($missing !== []) {
            throw ValidationException::withMessages([
                'file' => 'Format file tidak sesuai. Kolom wajib tidak ditemukan: '.implode(', ', $missing).'.',
            ]);
        }

        return $indexes;
    }

    /**
     * FASIH menulis kode wilayah sebagai formula Excel, mis. ="3101020001000600".
     */
    private function cleanRegionCode(string $rawCode): string
    {
        return preg_replace('/\D/', '', $rawCode) ?? '';
    }

    /**
     * Mengubah "APPROVED BY Pengawas:127 | DRAFT:22" menjadi ['APPROVED BY Pengawas' => 127, 'DRAFT' => 22].
     *
     * @return array<string, int>|null
     */
    private function parseStatusBreakdown(string $breakdown): ?array
    {
        if ($breakdown === '') {
            return [];
        }

        $counts = [];
        foreach (explode('|', $breakdown) as $part) {
            if (! preg_match('/^\s*(.+?)\s*:\s*(\d+)\s*$/', $part, $matches)) {
                return null;
            }
            $counts[$matches[1]] = ($counts[$matches[1]] ?? 0) + (int) $matches[2];
        }

        return $counts;
    }

    /**
     * @param  array<string, int>  $statusCounts
     * @return array{open: int, draft: int, submit: int}
     */
    private function groupStatusCounts(array $statusCounts): array
    {
        $grouped = ['open' => 0, 'draft' => 0, 'submit' => 0];
        $statusToGroup = [];
        foreach (config('fasih.status_groups', []) as $group => $statuses) {
            foreach ($statuses as $status) {
                $statusToGroup[strtoupper($status)] = $group;
            }
        }

        foreach ($statusCounts as $status => $count) {
            $group = $statusToGroup[strtoupper($status)] ?? null;
            if ($group !== null && array_key_exists($group, $grouped)) {
                $grouped[$group] += $count;
            }
        }

        return $grouped;
    }

    /**
     * Menautkan baris ke akun SIMPROCA yang emailnya sama dengan email pencacah di FASIH.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function attachUserIds(array $rows): array
    {
        $emails = array_values(array_unique(array_column($rows, 'email')));
        $userIdsByEmail = User::query()
            ->whereIn(DB::raw('LOWER(email)'), $emails)
            ->get(['id', 'email'])
            ->mapWithKeys(fn (User $user): array => [strtolower($user->email) => $user->id]);

        return array_map(function (array $row) use ($userIdsByEmail): array {
            $row['user_id'] = $userIdsByEmail->get($row['email']);

            return $row;
        }, $rows);
    }
}
