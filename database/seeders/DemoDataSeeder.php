<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\District;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyCheckpoint;
use App\Models\SurveyEntry;
use App\Models\SurveyVariable;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class DemoDataSeeder extends Seeder
{
    private Collection $districts;

    /**
     * Mengisi data palsu (survei, alokasi mitra, entri, checkpoint, notifikasi, log
     * aktivitas) agar seluruh fungsi SIMPROCA terisi penuh untuk uji coba.
     */
    public function run(): void
    {
        $pegawai = User::query()->where('email', 'pegawai@bps.go.id')->first();

        $mitras = User::role('mitra')->orderBy('id')->get();
        if ($mitras->isEmpty()) {
            $this->command?->warn('Tidak ada user mitra. Jalankan DatabaseSeeder dulu.');

            return;
        }

        $this->districts = District::query()->with('villages')->get();
        if ($this->districts->isEmpty()) {
            $this->command?->warn('Tidak ada kecamatan. Jalankan RegionSeeder dulu.');

            return;
        }

        SurveyEntry::query()->whereIn('survey_id', Survey::query()->pluck('id'))->delete();
        Survey::query()->delete();

        // Uji coba memakai dua survei saja: SUSENAS (PAPI) dan Sensus Ekonomi 2026 (CAPI).
        $this->seedFlagshipSurveys($pegawai, $mitras);
        $this->seedCapiSurvey($pegawai);

        $this->seedActivityLogs();

        $this->command?->info('DemoDataSeeder selesai: '.$mitras->count().' mitra, survei, checkpoint, notifikasi & log aktivitas dibuat.');
    }

    private function seedFlagshipSurveys(?User $pegawai, Collection $mitras): void
    {
        $susenasVariables = [
            ['Sudah Selesai? [sudah/belum]', 'text', 'sudah'],
            ['VSEN26.K BLOK II R203', 'number', '1'],
            ['VSEN26.KP BLOK II R203', 'number', '1'],
            ['VSEN26.K BLOK III R301', 'number', '4'],
            ['VSEN26.K BLOK III R302', 'number', '1'],
            ['VSEN26.K BLOK VI R617 (Pilih salah satu)', 'number', '1'],
            ['VSEN26.K BLOK XIII.A. R1302', 'number', '1'],
            ['VSEN26.K BLOK XVI R1604', 'number', '68'],
            ['VSEN26.K BLOK XVI R1610.A.', 'number', '2'],
            ['VSEN26.K BLOK XX R2010.A.(i)', 'number', '1250000'],
            ['VSEN26.KP BLOK III R304', 'number', '26'],
            ['VSEN26.KP BLOK III R305', 'number', '21'],
            ['VSEN26.KP BLOK IV.1 R2 KOLOM 9', 'number', '10.56'],
        ];

        $surveysData = [
            ['title' => 'SUSENAS', 'target' => 120, 'status' => 'Berjalan', 'fill' => 0.62, 'vars' => $susenasVariables],
        ];

        // Hanya sebagian mitra dialokasikan ke survei unggulan ini, sisanya
        // (dari total 50) dipakai untuk survei tim-tim lain agar realistis.
        $mitraSubset = $mitras->take(12)->values();

        foreach ($surveysData as $i => $data) {
            $start = Carbon::now()->subDays(28);
            $end = $data['status'] === 'Selesai' ? Carbon::now()->subDays(3) : Carbon::now()->addDays(20);

            $survey = Survey::query()->updateOrCreate(
                ['title' => $data['title']],
                [
                    'created_by' => $pegawai?->id,
                    'description' => 'Survei Sosial Ekonomi Nasional. Mitra mengisi form isian dan foto bukti pencacahan di SIMPROCA.',
                    'total_target' => $data['target'],
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                    'status' => $data['status'],
                ]
            );

            $variables = collect();
            foreach ($data['vars'] as [$name, $type, $exampleFormat]) {
                $variables->push(SurveyVariable::query()->updateOrCreate(
                    ['survey_id' => $survey->id, 'name' => $name],
                    ['data_type' => $type, 'example_format' => $exampleFormat]
                ));
            }

            SurveyEntry::query()->where('survey_id', $survey->id)->delete();

            $totalEntries = (int) round($data['target'] * $data['fill']);
            $mitraCount = $mitraSubset->count();
            $baseTarget = intdiv($data['target'], $mitraCount);
            $targetRemainder = $data['target'] % $mitraCount;
            $baseEntries = intdiv($totalEntries, $mitraCount);
            $entryRemainder = $totalEntries % $mitraCount;

            foreach ($mitraSubset as $mIndex => $mitra) {
                $target = max(1, $baseTarget + ($mIndex < $targetRemainder ? 1 : 0));
                $count = min($target, $baseEntries + ($mIndex < $entryRemainder ? 1 : 0));
                $assignment = SurveyAssignment::query()->updateOrCreate(
                    ['survey_id' => $survey->id, 'mitra_id' => $mitra->id],
                    ['target' => $target, 'current_progress' => 0]
                );

                for ($n = 0; $n < $count; $n++) {
                    $this->makeSubmittedEntry($survey, $assignment, $mitra, $variables, $n, $data['title'] === 'SUSENAS');
                }

                $assignment->update(['current_progress' => $count]);
            }

            $survey->update(['total_target' => (int) $survey->assignments()->sum('target')]);

            // Satu mitra dibuat tuntas agar contoh data punya mitra 100%, tanpa melebihi targetnya.
            if ($data['status'] === 'Berjalan' && $i === 0) {
                $assignment = SurveyAssignment::query()->where('survey_id', $survey->id)->with('mitra')->first();
                for ($k = (int) $assignment->current_progress; $k < (int) $assignment->target; $k++) {
                    $this->makeSubmittedEntry($survey, $assignment, $assignment->mitra, $variables, $k, true);
                }
                $assignment->update(['current_progress' => $assignment->target]);
            }

            if ($data['status'] === 'Berjalan') {
                $this->seedCheckpoints($survey);
            }
        }
    }

    /**
     * Membuat gambar contoh foto bukti di disk privat agar halaman detail entri tidak menampilkan
     * gambar rusak. Tanpa ekstensi GD, path tetap disimpan dan halaman menampilkan "Foto tidak ditemukan".
     */
    private function demoPhoto(string $path): string
    {
        $disk = Storage::disk(SurveyEntry::PHOTO_DISK);
        if ($disk->exists($path) || ! function_exists('imagecreatetruecolor')) {
            return $path;
        }

        $image = imagecreatetruecolor(480, 640);
        imagefill($image, 0, 0, imagecolorallocate($image, 226, 232, 240));
        imagefilledrectangle($image, 0, 560, 480, 640, imagecolorallocate($image, 11, 36, 71));
        $ink = imagecolorallocate($image, 71, 85, 105);
        imagerectangle($image, 150, 190, 330, 330, $ink);
        imagestring($image, 5, 150, 350, 'FOTO CONTOH', $ink);
        imagestring($image, 3, 16, 590, basename($path), imagecolorallocate($image, 255, 255, 255));

        ob_start();
        imagejpeg($image, null, 70);
        $disk->put($path, (string) ob_get_clean());
        imagedestroy($image);

        return $path;
    }

    private function makeSubmittedEntry(Survey $survey, SurveyAssignment $assignment, User $mitra, Collection $variables, int $n, bool $useSusenasValues): SurveyEntry
    {
        $district = $this->districts->random();
        $village = $district->villages->isNotEmpty() ? $district->villages->random() : null;
        $createdAt = Carbon::now()->subDays(random_int(0, 25))->setTime(random_int(7, 17), random_int(0, 59));

        $entry = SurveyEntry::query()->create([
            'survey_id' => $survey->id,
            'survey_assignment_id' => $assignment->id,
            'district_id' => $district->id,
            'village_id' => $village?->id,
            'kode_nks' => 'NKS'.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT),
            'sls' => 'SLS'.str_pad((string) $assignment->id, 2, '0', STR_PAD_LEFT),
            'ppl' => $mitra->name,
            'no_urut_ruta' => (string) ($n + 1),
            'evidence_photo_path' => $this->demoPhoto('demo/bukti-'.$survey->id.'-'.$assignment->id.'-'.($n + 1).'.jpg'),
            'entry_status' => 'submitted',
            'submitted_at' => $createdAt,
        ]);
        $entry->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();

        foreach ($variables as $variable) {
            $entry->values()->create([
                'survey_variable_id' => $variable->id,
                'value' => $useSusenasValues
                    ? $this->susenasValue($variable->name)
                    : ($variable->data_type === 'number' ? (string) random_int(1, 120) : 'Jawaban contoh '.($n + 1)),
            ]);
        }

        return $entry;
    }

    /**
     * Survei CAPI uji coba. Data progres diimpor admin dari CSV FASIH lewat halaman Monitoring.
     */
    private function seedCapiSurvey(?User $pegawai): void
    {
        Survey::query()->updateOrCreate(
            ['title' => 'Sensus Ekonomi 2026'],
            [
                'type' => Survey::TYPE_CAPI,
                'created_by' => $pegawai?->id ?? User::query()->value('id'),
                'description' => 'Pendataan usaha lewat aplikasi FASIH. Progres diperbarui dari data scraping FASIH.',
                'total_target' => 0,
                'start_date' => Carbon::now()->subDays(28)->toDateString(),
                'end_date' => Carbon::now()->addDays(31)->toDateString(),
                'status' => 'Berjalan',
            ]
        );
    }

    private function seedCheckpoints(Survey $survey): void
    {
        $overallPercentage = $survey->total_target > 0
            ? round(($survey->assignments()->sum('current_progress') / $survey->total_target) * 100)
            : 0;

        $start = Carbon::parse($survey->start_date);
        $end = Carbon::parse($survey->end_date);
        $today = Carbon::today();

        $pastDate = $start->copy()->addDays((int) $start->diffInDays($today) / 2);
        if ($pastDate->gt($today)) {
            $pastDate = $today->copy()->subDays(3);
        }

        SurveyCheckpoint::query()->create([
            'survey_id' => $survey->id,
            'checkpoint_date' => $pastDate->toDateString(),
            'target_percentage' => max(1, min(99, (int) $overallPercentage - 15)),
        ]);

        SurveyCheckpoint::query()->create([
            'survey_id' => $survey->id,
            'checkpoint_date' => $today->toDateString(),
            'target_percentage' => max(2, min(100, (int) $overallPercentage + 15)),
        ]);

        if ($end->gt($today)) {
            $futureDate = $today->copy()->addDays(max(1, (int) $today->diffInDays($end) / 2));
            SurveyCheckpoint::query()->create([
                'survey_id' => $survey->id,
                'checkpoint_date' => $futureDate->toDateString(),
                'target_percentage' => 95,
            ]);
        }
    }

    private function seedActivityLogs(): void
    {
        $admin = User::query()->where('email', 'admin@bps.go.id')->first();
        if (! $admin) {
            return;
        }

        ActivityLog::query()->delete();

        // Log contoh memakai kode aksi yang sama dengan aplikasi: admin membuat survei lalu mengimpor alokasi.
        $entries = [];
        foreach (Survey::query()->withCount('assignments')->withSum('assignments', 'target')->get() as $survey) {
            $createdAt = Carbon::parse($survey->start_date)->subDays(2)->setTime(9, 0)->toDateTimeString();
            $entries[] = [
                'user_id' => $admin->id,
                'action' => 'admin.survey.create',
                'description' => 'Membuat survei '.$survey->typeLabel().' '.$survey->title,
                'meta' => null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];

            if ($survey->assignments_count > 0) {
                $importedAt = Carbon::parse($survey->start_date)->subDay()->setTime(10, 30)->toDateTimeString();
                $entries[] = [
                    'user_id' => $admin->id,
                    'action' => 'admin.survey.allocation_import',
                    'description' => "Mengimpor alokasi {$survey->assignments_sum_target} ruta untuk {$survey->assignments_count} mitra pada survei {$survey->title}",
                    'meta' => null,
                    'created_at' => $importedAt,
                    'updated_at' => $importedAt,
                ];
            }
        }

        foreach (array_chunk($entries, 200) as $chunk) {
            ActivityLog::query()->insert($chunk);
        }
    }

    private function susenasValue(string $variableName): string
    {
        if (str_contains($variableName, 'Sudah Selesai')) {
            return 'sudah';
        }

        if (str_contains($variableName, 'R2010')) {
            return collect(['0', '1250000', '3600000', '4000000'])->random();
        }

        if (str_contains($variableName, 'KOLOM 9')) {
            return collect(['10.56', '6.23', '3.11', '5.95'])->random();
        }

        if (str_contains($variableName, 'R1604')) {
            return (string) random_int(24, 120);
        }

        if (str_contains($variableName, 'R304')) {
            return (string) random_int(26, 127);
        }

        if (str_contains($variableName, 'R305')) {
            return (string) random_int(14, 41);
        }

        return (string) collect(['-', '0', '1', '2', '3', '4', '5'])->random();
    }
}
