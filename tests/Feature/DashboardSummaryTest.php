<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\SurveyEntry;
use App\Models\User;
use App\Models\Village;
use App\Services\SurveyDashboardOverview;
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
        foreach ([SurveyEntry::STATUS_SUBMITTED, SurveyEntry::STATUS_DRAFT, SurveyEntry::STATUS_OPEN] as $index => $status) {
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

        // 3 ruta berwilayah (1 submit, 1 draft, 1 open) + 2 sisa target tanpa wilayah (open).
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
        $this->assertSame(($baselineDistrict['total'] ?? 0) + 3, $district['total']);

        $villageRow = $district['villages']->firstWhere('name', $village->name);
        $baselineVillage = collect($baselineDistrict['villages'] ?? [])->firstWhere('name', $village->name);
        $this->assertSame(($baselineVillage['total'] ?? 0) + 3, $villageRow['total']);
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
}
