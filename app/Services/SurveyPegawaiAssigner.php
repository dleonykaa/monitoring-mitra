<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Survey;
use App\Models\User;
use App\Notifications\PegawaiAssignedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Menugaskan pegawai BPS sebagai penanggung jawab survei, baik dari pilihan admin maupun dari
 * template Excel/CSV. Pegawai dikenali dari email akunnya; satu email salah membatalkan seluruh impor.
 */
class SurveyPegawaiAssigner
{
    private const MAX_REPORTED_ERRORS = 10;

    /**
     * Pegawai aktif dari file template, tanpa duplikat.
     *
     * @return Collection<int, User>
     *
     * @throws ValidationException
     */
    public function parse(UploadedFile $file, string $errorKey = 'file'): Collection
    {
        $sheet = Excel::toArray([], $file)[0] ?? [];
        $fail = fn (string $message) => throw ValidationException::withMessages([$errorKey => ['Upload dibatalkan, tidak ada pegawai yang ditugaskan. '.$message]]);

        [$headerIndex, $emailColumn] = $this->locateEmailColumn($sheet)
            ?? $fail('Kolom Email tidak ditemukan. Gunakan template pegawai dari SIMPROCA.');

        $pegawaiByEmail = User::query()->role('pegawai_bps')->where('is_active', true)->get()
            ->keyBy(fn (User $user): string => strtolower($user->email));

        $errors = [];
        $found = collect();

        foreach (array_slice($sheet, $headerIndex + 1, null, true) as $index => $row) {
            $joined = implode(' ', array_map(fn ($value): string => trim((string) $value), $row));
            if (trim($joined) === '' || str_contains(strtoupper($joined), 'CONTOH PENGISIAN')) {
                continue;
            }

            $email = strtolower(trim((string) ($row[$emailColumn] ?? '')));
            $pegawai = $pegawaiByEmail->get($email);

            if ($pegawai) {
                $found->put($pegawai->id, $pegawai);
            } else {
                $errors[] = 'Baris '.($index + 1).': '.($email === '' ? 'Email pegawai wajib diisi.' : "Email '{$email}' bukan akun pegawai BPS aktif.");
            }

            if (count($errors) >= self::MAX_REPORTED_ERRORS) {
                $errors[] = 'Periksa juga baris-baris berikutnya.';
                break;
            }
        }

        if ($errors !== []) {
            $fail(implode(' ', $errors));
        }
        if ($found->isEmpty()) {
            $fail('Tidak ada email pegawai yang bisa diproses. Isi baris di bawah judul kolom.');
        }

        return $found->values();
    }

    /**
     * Menugaskan pegawai yang belum menjadi penanggung jawab, lalu memberi tahu dan mencatatnya.
     *
     * @param  iterable<User>  $pegawai
     * @return Collection<int, User> Pegawai yang baru ditugaskan.
     */
    public function assign(Survey $survey, iterable $pegawai, User $admin): Collection
    {
        $assignedIds = $survey->pegawai()->pluck('users.id');
        $newPegawai = collect($pegawai)->unique('id')->reject(fn (User $user): bool => $assignedIds->contains($user->id))->values();

        $survey->pegawai()->attach($newPegawai->pluck('id')->all());

        $newPegawai->each(function (User $user) use ($survey, $admin): void {
            $user->notify(new PegawaiAssignedNotification($survey));

            ActivityLog::query()->create([
                'user_id' => $admin->id,
                'action' => 'admin.survey.pegawai_assign',
                'description' => "Menugaskan {$user->name} sebagai penanggung jawab survei {$survey->title}",
            ]);
        });

        return $newPegawai;
    }

    /**
     * Baris judul (boleh bukan baris pertama) dan posisi kolom Email.
     *
     * @param  array<int, array<int, mixed>>  $sheet
     * @return array{0: int, 1: int}|null
     */
    private function locateEmailColumn(array $sheet): ?array
    {
        foreach (array_slice($sheet, 0, 10, true) as $index => $row) {
            foreach ($row as $position => $value) {
                if (strtolower(trim((string) $value)) === 'email') {
                    return [$index, $position];
                }
            }
        }

        return null;
    }
}
