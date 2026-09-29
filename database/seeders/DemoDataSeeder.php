<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyCheckpoint;
use App\Models\SurveyEntry;
use App\Models\SurveyVariable;
use App\Models\Team;
use App\Models\User;
use App\Notifications\EntryInvalidNotification;
use App\Services\EntryAnomalyValidator;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    private Collection $districts;

    private array $respondents = [
        'Budi Santoso', 'Siti Aminah', 'Agus Wijaya', 'Dewi Lestari', 'Rudi Hartono',
        'Nur Halimah', 'Joko Susilo', 'Rina Marlina', 'Hendra Gunawan', 'Lia Kurnia',
        'Bambang Iswanto', 'Sri Wahyuni', 'Eko Prasetyo', 'Maya Sari', 'Andi Saputra',
        'Tuti Handayani', 'Doni Firmansyah', 'Ratna Sari', 'Fajar Nugroho', 'Indah Permata',
    ];

    /**
     * Mengisi data palsu (tim, survei, alokasi mitra, entri, checkpoint, notifikasi, log
     * aktivitas) agar seluruh fungsi SIMKM terisi penuh untuk uji coba.
     */
    public function run(): void
    {
        $sosialTeam = Team::query()->where('name', 'Tim Fungsi Sosial')->firstOrFail();
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

        $this->seedFlagshipSurveys($sosialTeam, $pegawai, $mitras);

        // Survei tambahan untuk 10 tim lain (skala besar) di-skip saat testing
        // agar suite tes tetap cepat; baseline flagship di atas sudah cukup
        // untuk seluruh skenario tes yang ada.
        if (! app()->environment('testing')) {
            $this->seedTeamSurveys($mitras);
        }

        $this->seedPegawaiNotifications();
        $this->seedActivityLogs();

        $this->command?->info('DemoDataSeeder selesai: '.$mitras->count().' mitra, survei, checkpoint, notifikasi & log aktivitas dibuat.');
    }

    private function seedFlagshipSurveys(Team $team, ?User $pegawai, Collection $mitras): void
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

        $susenasFormula = EntryAnomalyValidator::variableIdentifier('VSEN26.K BLOK XVI R1604').' >= 0 and '
            .EntryAnomalyValidator::variableIdentifier('VSEN26.K BLOK XVI R1604').' <= 100';

        $surveysData = [
            ['title' => 'SUSENAS', 'target' => 120, 'status' => 'Berjalan', 'fill' => 0.62, 'vars' => $susenasVariables, 'formula' => $susenasFormula],
            ['title' => 'Survei Angkatan Kerja Nasional (Sakernas)', 'target' => 90, 'status' => 'Berjalan', 'fill' => 0.78, 'vars' => [['Status Pekerjaan', 'text', 'Bekerja']], 'formula' => null],
            ['title' => 'Survei Biaya Hidup (SBH) 2026', 'target' => 80, 'status' => 'Berjalan', 'fill' => 0.40, 'vars' => [], 'formula' => null],
            ['title' => 'Pendataan Potensi Desa (Podes)', 'target' => 60, 'status' => 'Selesai', 'fill' => 1.00, 'vars' => [['Jumlah RT', 'number', '12']], 'formula' => null],
        ];

        // Hanya sebagian mitra dialokasikan ke survei unggulan ini, sisanya
        // (dari total 50) dipakai untuk survei tim-tim lain agar realistis.
        $mitraSubset = $mitras->take(12)->values();

        foreach ($surveysData as $i => $data) {
            $start = Carbon::now()->subDays(28);
            $end = $data['status'] === 'Selesai' ? Carbon::now()->subDays(3) : Carbon::now()->addDays(20);

            $survey = Survey::query()->updateOrCreate(
                ['team_id' => $team->id, 'title' => $data['title']],
                [
                    'created_by' => $pegawai?->id,
                    'description' => 'Data contoh untuk demonstrasi dashboard SIMKM.',
                    'total_target' => $data['target'],
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                    'status' => $data['status'],
                    'validation_formula' => $data['formula'],
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
                    $entry = $this->makeSubmittedEntry($survey, $assignment, $mitra, $variables, $n, $data['title'] === 'SUSENAS');
                    EntryAnomalyValidator::apply($entry);
                    $this->notifyIfInvalid($entry);
                }

                $assignment->update(['current_progress' => $count]);
            }

            $survey->update(['total_target' => (int) $survey->assignments()->sum('target')]);

            if ($data['status'] === 'Berjalan' && $i === 0) {
                $assignment = SurveyAssignment::query()->where('survey_id', $survey->id)->first();
                for ($k = 0; $k < 5; $k++) {
                    $todayEntry = $this->makeSubmittedEntry($survey, $assignment, $assignment->mitra, $variables, $k, true);
                    EntryAnomalyValidator::apply($todayEntry);
                }
                $assignment->increment('current_progress', 5);
            }

            if ($data['status'] === 'Berjalan') {
                $this->seedCheckpoints($survey);
            }
        }
    }

    private function seedTeamSurveys(Collection $mitras): void
    {
        $teams = Team::query()->where('name', '!=', 'Tim Fungsi Sosial')->orderBy('id')->get();
        $mitraCount = $mitras->count();
        $slice = 8;

        foreach ($teams as $ti => $team) {
            $pegawai = User::role('pegawai_bps')->whereHas('teams', fn ($q) => $q->where('teams.id', $team->id))->first();
            $shortName = trim(str_replace('Tim ', '', $team->name));

            // Survei berjalan: dialokasikan via pola "template upload" (kode_nks/sls/ppl
            // terisi, sebagian entri masih draft menunggu mitra melengkapi) + sebagian
            // entri submitted (ada yang valid, ada yang anomali) untuk uji fitur validasi.
            $offset = ($ti * 5) % $mitraCount;
            $teamMitras = $this->wrappedSlice($mitras, $offset, $slice);
            $isOverdue = $ti % 3 === 0;
            $target = 40 + ($ti * 6) % 60;

            $runningSurvey = Survey::query()->create([
                'team_id' => $team->id,
                'created_by' => $pegawai?->id,
                'title' => 'Survei '.$shortName.' '.now()->year,
                'description' => 'Survei pemantauan kinerja untuk '.$team->name.'.',
                'total_target' => $target,
                'start_date' => Carbon::now()->subDays(30)->toDateString(),
                'end_date' => $isOverdue ? Carbon::now()->subDays(4)->toDateString() : Carbon::now()->addDays(18)->toDateString(),
                'status' => 'Berjalan',
                'validation_formula' => 'nilai_utama >= 0 and nilai_utama <= 100',
            ]);

            $variables = collect([
                SurveyVariable::query()->create(['survey_id' => $runningSurvey->id, 'name' => 'Nilai Utama', 'data_type' => 'number', 'example_format' => '45']),
                SurveyVariable::query()->create(['survey_id' => $runningSurvey->id, 'name' => 'Catatan Lapangan', 'data_type' => 'text', 'example_format' => 'Lengkap']),
            ]);

            $baseTarget = intdiv($target, $teamMitras->count());
            $remainder = $target % $teamMitras->count();
            $progressTotal = 0;

            foreach ($teamMitras as $mIndex => $mitra) {
                $mitraTarget = max(2, $baseTarget + ($mIndex < $remainder ? 1 : 0));
                $assignment = SurveyAssignment::query()->create([
                    'survey_id' => $runningSurvey->id,
                    'mitra_id' => $mitra->id,
                    'target' => $mitraTarget,
                    'current_progress' => 0,
                ]);

                $submittedCount = max(1, (int) round($mitraTarget * 0.55));
                for ($n = 0; $n < $submittedCount; $n++) {
                    $forceInvalid = $n % 5 === 4; // ~20% dibuat anomali untuk uji "Perlu Perhatian"
                    $entry = $this->makeTemplateEntry($runningSurvey, $assignment, $mitra, 'submitted', $n);
                    $entry->values()->create([
                        'survey_variable_id' => $variables[0]->id,
                        'value' => $forceInvalid ? (string) random_int(150, 300) : (string) random_int(0, 100),
                    ]);
                    $entry->values()->create([
                        'survey_variable_id' => $variables[1]->id,
                        'value' => $forceInvalid ? 'Perlu dicek ulang' : 'Lengkap dan sesuai',
                    ]);
                    EntryAnomalyValidator::apply($entry);
                    $this->notifyIfInvalid($entry);
                }

                // Sisa alokasi dibuat sebagai entri draft hasil "upload template"
                // (kode_nks/sls/ppl/no_urut_ruta sudah terisi, menunggu mitra mengisi nilai).
                $pendingCount = max(0, min(3, $mitraTarget - $submittedCount));
                for ($n = 0; $n < $pendingCount; $n++) {
                    $this->makeTemplateEntry($runningSurvey, $assignment, $mitra, 'draft', $submittedCount + $n);
                }

                $assignment->update(['current_progress' => $submittedCount]);
                $progressTotal += $submittedCount;
            }

            $this->seedCheckpoints($runningSurvey);

            // Survei kedua per tim: variasi status (Selesai / Draft) agar semua
            // status survei terwakili di seluruh sistem.
            if ($ti % 2 === 0) {
                $doneTarget = 25 + ($ti % 4) * 5;
                $doneSurvey = Survey::query()->create([
                    'team_id' => $team->id,
                    'created_by' => $pegawai?->id,
                    'title' => 'Survei '.$shortName.' '.(now()->year - 1),
                    'description' => 'Survei tahun lalu, sudah selesai seluruhnya.',
                    'total_target' => $doneTarget,
                    'start_date' => Carbon::now()->subDays(90)->toDateString(),
                    'end_date' => Carbon::now()->subDays(30)->toDateString(),
                    'status' => 'Selesai',
                ]);
                $doneVariable = SurveyVariable::query()->create(['survey_id' => $doneSurvey->id, 'name' => 'Jumlah Responden', 'data_type' => 'number', 'example_format' => '5']);

                $doneMitras = $this->wrappedSlice($mitras, ($offset + 3) % $mitraCount, 4);
                $doneBase = intdiv($doneTarget, $doneMitras->count());
                $doneRemainder = $doneTarget % $doneMitras->count();

                foreach ($doneMitras as $mIndex => $mitra) {
                    $mitraTarget = max(1, $doneBase + ($mIndex < $doneRemainder ? 1 : 0));
                    $assignment = SurveyAssignment::query()->create([
                        'survey_id' => $doneSurvey->id,
                        'mitra_id' => $mitra->id,
                        'target' => $mitraTarget,
                        'current_progress' => $mitraTarget,
                    ]);

                    for ($n = 0; $n < $mitraTarget; $n++) {
                        $entry = $this->makeTemplateEntry($doneSurvey, $assignment, $mitra, 'submitted', $n);
                        $entry->values()->create(['survey_variable_id' => $doneVariable->id, 'value' => (string) random_int(3, 10)]);
                        EntryAnomalyValidator::apply($entry);
                    }
                }
            } else {
                Survey::query()->create([
                    'team_id' => $team->id,
                    'created_by' => $pegawai?->id,
                    'title' => 'Draft Survei '.$shortName,
                    'description' => 'Survei baru, masih disiapkan pegawai (belum dijalankan).',
                    'total_target' => 0,
                    'start_date' => Carbon::now()->addDays(5)->toDateString(),
                    'end_date' => Carbon::now()->addDays(35)->toDateString(),
                    'status' => 'Draft',
                ]);
            }
        }
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
            'sls' => 'SLS'.str_pad((string) random_int(1, 20), 2, '0', STR_PAD_LEFT),
            'ppl' => $mitra->name,
            'no_urut_ruta' => (string) ($n + 1),
            'respondent_name' => $this->respondents[array_rand($this->respondents)],
            'evidence_photo_path' => 'demo/bukti-'.$survey->id.'-'.$assignment->id.'-'.($n + 1).'.jpg',
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

    private function makeTemplateEntry(Survey $survey, SurveyAssignment $assignment, User $mitra, string $status, int $n): SurveyEntry
    {
        $district = $this->districts->random();
        $village = $district->villages->isNotEmpty() ? $district->villages->random() : null;
        $createdAt = Carbon::now()->subDays(random_int(0, 20))->setTime(random_int(7, 17), random_int(0, 59));

        $entry = SurveyEntry::query()->create([
            'survey_id' => $survey->id,
            'survey_assignment_id' => $assignment->id,
            'district_id' => $district->id,
            'village_id' => $village?->id,
            'kode_nks' => 'NKS'.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT),
            'sls' => 'SLS'.str_pad((string) random_int(1, 20), 2, '0', STR_PAD_LEFT),
            'ppl' => $mitra->name,
            'no_urut_ruta' => (string) ($n + 1),
            'evidence_photo_path' => $status === 'submitted' ? 'demo/bukti-'.$survey->id.'-'.$assignment->id.'-'.($n + 1).'.jpg' : null,
            'entry_status' => $status,
            'submitted_at' => $status === 'submitted' ? $createdAt : null,
        ]);
        $entry->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();

        return $entry;
    }

    private function notifyIfInvalid(SurveyEntry $entry): void
    {
        $entry->refresh();
        if ($entry->is_valid === false) {
            $entry->assignment->mitra->notify(new EntryInvalidNotification($entry->load(['survey', 'assignment.mitra'])));
        }
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

    private function seedPegawaiNotifications(): void
    {
        $pegawaiUsers = User::role('pegawai_bps')->get();

        foreach ($pegawaiUsers as $pegawai) {
            $pegawai->notifications()->delete();
            $teamIds = $pegawai->teams()->pluck('teams.id');
            $surveys = Survey::query()->whereIn('team_id', $teamIds)->get();
            if ($surveys->isEmpty()) {
                continue;
            }

            $mitraNames = SurveyAssignment::query()
                ->whereIn('survey_id', $surveys->pluck('id'))
                ->with('mitra')
                ->get()
                ->pluck('mitra.name')
                ->filter()
                ->unique()
                ->values();
            if ($mitraNames->isEmpty()) {
                continue;
            }

            $notifCount = random_int(2, 5);
            for ($n = 0; $n < $notifCount; $n++) {
                $mitraName = $mitraNames->random();
                $survey = $surveys->random();
                $added = random_int(1, 5);
                $createdAt = Carbon::now()->subMinutes($n * 53 + random_int(0, 40));

                $pegawai->notifications()->create([
                    'id' => (string) Str::uuid(),
                    'type' => 'App\\Notifications\\MitraProgressUpdated',
                    'data' => [
                        'title' => 'Progress baru dari mitra',
                        'message' => $mitraName.' mengisi '.$added.' entri baru pada survei "'.Str::limit($survey->title, 40).'".',
                        'mitra' => $mitraName,
                        'survey' => $survey->title,
                        'url' => '/pegawai/updates',
                    ],
                    'read_at' => $n < 1 ? null : $createdAt,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        }
    }

    private function seedActivityLogs(): void
    {
        $admin = User::query()->where('email', 'admin@bps.go.id')->first();
        if (! $admin) {
            return;
        }

        \App\Models\ActivityLog::query()->delete();

        $teams = Team::query()->get();
        $entries = [];
        $now = Carbon::now();

        foreach ($teams as $i => $team) {
            $entries[] = [
                'user_id' => $admin->id,
                'action' => 'admin.team.create',
                'description' => 'Membuat tim '.$team->name,
                'meta' => null,
                'created_at' => $now->copy()->subDays(60 - $i)->toDateTimeString(),
                'updated_at' => $now->copy()->subDays(60 - $i)->toDateTimeString(),
            ];
        }

        foreach (Survey::query()->with('assignments')->get() as $survey) {
            $creator = $survey->created_by ?: $admin->id;
            $entries[] = [
                'user_id' => $creator,
                'action' => 'pegawai.survey.create',
                'description' => 'Membuat survei '.$survey->title,
                'meta' => null,
                'created_at' => Carbon::parse($survey->start_date)->subDays(2)->toDateTimeString(),
                'updated_at' => Carbon::parse($survey->start_date)->subDays(2)->toDateTimeString(),
            ];

            foreach ($survey->assignments as $assignment) {
                $entries[] = [
                    'user_id' => $creator,
                    'action' => 'pegawai.assignment.create',
                    'description' => 'Alokasi mitra ke survei '.$survey->title,
                    'meta' => null,
                    'created_at' => Carbon::parse($survey->start_date)->subDay()->toDateTimeString(),
                    'updated_at' => Carbon::parse($survey->start_date)->subDay()->toDateTimeString(),
                ];
            }
        }

        foreach (array_chunk($entries, 200) as $chunk) {
            \App\Models\ActivityLog::query()->insert($chunk);
        }
    }

    /**
     * @param  Collection<int, User>  $collection
     * @return Collection<int, User>
     */
    private function wrappedSlice(Collection $collection, int $offset, int $length): Collection
    {
        $items = $collection->values()->all();
        $count = count($items);
        $length = min($length, $count);
        $result = [];
        for ($i = 0; $i < $length; $i++) {
            $result[] = $items[($offset + $i) % $count];
        }

        return collect($result);
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
