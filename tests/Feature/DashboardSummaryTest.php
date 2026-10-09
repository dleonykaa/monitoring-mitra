<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\SurveyEntry;
use App\Models\User;
use App\Models\Village;
use App\Services\SurveyDashboardOverview;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_summarises_running_papi_survey_like_monitoring(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@bps.go.id')->firstOrFail();
        $mitra = User::query()->where('email', 'mitra@bps.go.id')->firstOrFail();
        $village = Village::query()->whereNotNull('code')->with('district')->firstOrFail();

        $baseline = app(SurveyDashboardOverview::class)->build();

        $survey = Survey::query()->create([
            'type' => Survey::TYPE_PAPI,
            'title' => 'Survei Ringkasan Dashboard',
            'start_date' => now()->subWeek()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'status' => 'Berjalan',
            'created_by' => $admin->id,
            'total_target' => 5,
        ]);
        $assignment = $survey->assignments()->create(['mitra_id' => $mitra->id, 'target' => 5]);
        foreach ([SurveyEntry::STATUS_SUBMITTED, SurveyEntry::STATUS_DRAFT, SurveyEntry::STATUS_OPEN, SurveyEntry::STATUS_OPEN, SurveyEntry::STATUS_OPEN] as $index => $status) {
            SurveyEntry::query()->create([
                'survey_id' => $survey->id,
                'survey_assignment_id' => $assignment->id,
                'district_id' => $village->district_id,
                'village_id' => $village->id,
                'sls' => '0001',
                'no_urut_ruta' => (string) ($index + 1),
                'entry_status' => $status,
                'submitted_at' => $status === SurveyEntry::STATUS_SUBMITTED ? now() : null,
            ]);
        }

        $summary = app(SurveyDashboardOverview::class)->build();

        // 5 ruta hasil alokasi: 1 submit, 1 draft, 3 open.
        $row = $summary['surveys']->firstWhere('id', $survey->id);
        $this->assertSame(['total' => 5, 'open' => 3, 'draft' => 1, 'submit' => 1], array_intersect_key($row->breakdown, array_flip(['total', 'open', 'draft', 'submit'])));
        $this->assertSame(20.0, $row->progress_percent);

        $this->assertSame($baseline['runningTotals']['total'] + 5, $summary['runningTotals']['total']);
        $this->assertSame($baseline['runningTotals']['submit'] + 1, $summary['runningTotals']['submit']);
        $todayCount = fn (array $data): int => $data['dailyEntries']->first(fn (array $day): bool => $day['date']->isSameDay(now('Asia/Jakarta')))['count'];
        $this->assertSame($todayCount($baseline) + 1, $todayCount($summary));
        // Grafik harian selalu satu minggu kalender, Senin sampai Minggu.
        $this->assertCount(7, $summary['dailyEntries']);
        $this->assertTrue($summary['dailyEntries']->first()['date']->isMonday());
        $this->assertTrue($summary['dailyEntries']->last()['date']->isSunday());

        $district = $summary['districtSummary']->firstWhere('name', $village->district->name);
        $baselineDistrict = $baseline['districtSummary']->firstWhere('name', $village->district->name);
        $this->assertSame(($baselineDistrict['total'] ?? 0) + 5, $district['total']);

        $villageRow = $district['villages']->firstWhere('name', $village->name);
        $baselineVillage = collect($baselineDistrict['villages'] ?? [])->firstWhere('name', $village->name);
        $this->assertSame(($baselineVillage['total'] ?? 0) + 5, $villageRow['total']);
        $this->assertSame(($baselineVillage['submit'] ?? 0) + 1, $villageRow['submit']);
        $this->assertSame($district['total'], $district['villages']->sum('total'));

        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Survei Ringkasan Dashboard')
            ->assertSee('Sisa 3 hari')
            // Progres 20% padahal sebagian besar waktu survei sudah lewat.
            ->assertSee('Tertinggal jadwal')
            ->assertDontSee('href="/admin/monitoring/progres?survey='.$survey->id.'"', false)
            ->assertDontSee('beban PAPI belum punya wilayah')
            ->assertSee('db-dist-sub', false)
            ->assertSee($village->name);
    }

    public function test_overdue_running_survey_is_flagged_in_survey_list(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@bps.go.id')->firstOrFail();
        Survey::query()->create([
            'type' => Survey::TYPE_PAPI,
            'title' => 'Survei Terlambat Dashboard',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->subDays(2)->toDateString(),
            'status' => 'Berjalan',
            'created_by' => $admin->id,
            'total_target' => 0,
        ]);

        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Survei Terlambat Dashboard')
            ->assertSee('Lewat 2 hari');
    }

    public function test_survey_type_chips_count_only_running_surveys(): void
    {
        [$admin] = $this->accounts(1);
        foreach ([[Survey::TYPE_PAPI, 'Berjalan'], [Survey::TYPE_PAPI, 'Berjalan'], [Survey::TYPE_CAPI, 'Berjalan'], [Survey::TYPE_CAPI, 'Selesai'], [Survey::TYPE_PAPI, 'Draft']] as $index => [$type, $status]) {
            $this->makeSurvey($admin, 'Survei Jenis '.$index, $type, $status);
        }

        $summary = app(SurveyDashboardOverview::class)->build();

        $this->assertSame(['PAPI' => 2, 'CAPI' => 1], $summary['surveyTypeCounts']);
        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSeeInOrder(['3 <small>berjalan</small>', '2 PAPI', '1 CAPI'], false);
    }

    public function test_mitra_below_target_uses_cumulative_progress_against_checkpoint(): void
    {
        [$admin, $behind, $onTrack, $capiBehind] = $this->accounts(4);

        $papi = $this->makeSurvey($admin, 'Sakernas Uji', Survey::TYPE_PAPI);
        $papi->assignments()->create(['mitra_id' => $behind->id, 'target' => 20, 'current_progress' => 4]);
        $papi->assignments()->create(['mitra_id' => $onTrack->id, 'target' => 20, 'current_progress' => 12]);
        $papi->checkpoints()->create(['checkpoint_date' => now()->subDay()->toDateString(), 'target_percentage' => 50]);

        // CAPI: dua wilayah 9/10 dan 0/30 digabung menjadi 9/40 (22,5%), bukan dinilai per wilayah.
        $capi = $this->makeSurvey($admin, 'SE2026 Uji', Survey::TYPE_CAPI);
        $import = $capi->fasihImports()->create(['user_id' => $admin->id, 'file_name' => 'uji.csv', 'row_count' => 2]);
        foreach ([['0001', 10, 9], ['0002', 30, 0]] as [$sls, $total, $submit]) {
            $import->rows()->create([
                'user_id' => $capiBehind->id, 'email' => strtolower($capiBehind->email), 'pencacah_name' => $capiBehind->name,
                'region_code' => '3101010001'.$sls.'00', 'district_code' => '3101010', 'village_code' => '3101010001', 'sls_code' => '3101010001'.$sls,
                'total_region' => $total, 'open_count' => $total - $submit, 'draft_count' => 0, 'submit_count' => $submit, 'status_breakdown' => [],
            ]);
        }
        $capi->checkpoints()->create(['checkpoint_date' => now()->subDay()->toDateString(), 'target_percentage' => 50]);

        $behindMitra = app(SurveyDashboardOverview::class)->build()['behindMitra'];

        $this->assertSame([$capiBehind->name, $behind->name], $behindMitra->pluck('name')->all());
        $this->assertSame(['progress' => 9, 'target' => 40, 'percent' => 22.5, 'gap' => 11, 'unit' => 'dokumen'], array_intersect_key($behindMitra[0], array_flip(['progress', 'target', 'percent', 'gap', 'unit'])));
        $this->assertSame(['progress' => 4, 'target' => 20, 'gap' => 6, 'unit' => 'ruta'], array_intersect_key($behindMitra[1], array_flip(['progress', 'target', 'gap', 'unit'])));

        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSeeInOrder(['Mitra di bawah target', $capiBehind->name, 'SE2026 Uji · 9/40 dokumen (22,5%) · target 50,0%', 'kurang 11', $behind->name, 'Sakernas Uji · 4/20 ruta (20,0%) · target 50,0%', 'kurang 6'])
            ->assertDontSee($onTrack->name)
            ->assertDontSee('Mitra belum update');
    }

    public function test_mitra_list_follows_schedule_without_checkpoint_and_stays_empty_early(): void
    {
        [$admin, $mitra] = $this->accounts(2);

        // Hari pertama survei: belum ada entri, tetapi porsi waktu masih kecil sehingga belum dianggap tertinggal.
        $early = $this->makeSurvey($admin, 'Survei Baru Mulai', Survey::TYPE_PAPI, start: now()->toDateString(), end: now()->addDays(29)->toDateString());
        $early->assignments()->create(['mitra_id' => $mitra->id, 'target' => 10, 'current_progress' => 0]);

        $this->assertTrue(app(SurveyDashboardOverview::class)->build()['behindMitra']->isEmpty());
        $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->assertSee('Tidak ada mitra yang tertinggal');

        // Survei yang sudah lewat tenggat: patokannya 100% waktu berjalan.
        $late = $this->makeSurvey($admin, 'Survei Lewat Tenggat', Survey::TYPE_PAPI, start: now()->subMonth()->toDateString(), end: now()->subDay()->toDateString());
        $late->assignments()->create(['mitra_id' => $mitra->id, 'target' => 10, 'current_progress' => 7]);

        $behindMitra = app(SurveyDashboardOverview::class)->build()['behindMitra'];
        $this->assertCount(1, $behindMitra);
        $this->assertSame(['benchmark' => 100.0, 'gap' => 3], array_intersect_key($behindMitra[0], array_flip(['benchmark', 'gap'])));
        $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->assertSee('Survei Lewat Tenggat · 7/10 ruta (70,0%) · jadwal 100,0%');
    }

    /**
     * @return list<User>
     */
    private function accounts(int $count): array
    {
        $this->seed(RolePermissionSeeder::class);

        return collect(range(1, $count))->map(function (int $index): User {
            $user = User::factory()->create(['is_active' => true]);
            $user->assignRole($index === 1 ? 'admin' : 'mitra');

            return $user;
        })->all();
    }

    private function makeSurvey(User $admin, string $title, string $type, string $status = 'Berjalan', ?string $start = null, ?string $end = null): Survey
    {
        return Survey::query()->create([
            'type' => $type,
            'title' => $title,
            'start_date' => $start ?? now()->subWeek()->toDateString(),
            'end_date' => $end ?? now()->addWeek()->toDateString(),
            'status' => $status,
            'created_by' => $admin->id,
            'total_target' => 0,
        ]);
    }
}
