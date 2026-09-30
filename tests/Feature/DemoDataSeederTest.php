<?php

namespace Tests\Feature;

use App\Models\SurveyAssignment;
use App\Models\SurveyEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_is_consistent(): void
    {
        Storage::fake('public');
        $this->seed();

        $duplicates = SurveyEntry::query()
            ->whereNotNull('no_urut_ruta')
            ->selectRaw('survey_id, village_id, sls, no_urut_ruta, count(*) as total')
            ->groupBy('survey_id', 'village_id', 'sls', 'no_urut_ruta')
            ->havingRaw('count(*) > 1')
            ->count();
        $this->assertSame(0, $duplicates, 'Tidak boleh ada ruta ganda (desa + SLS + no urut) dalam satu survei.');

        SurveyAssignment::query()->withCount([
            'entries',
            'entries as submitted_count' => fn ($query) => $query->where('entry_status', SurveyEntry::STATUS_SUBMITTED),
        ])->get()->each(function (SurveyAssignment $assignment): void {
            $this->assertLessThanOrEqual($assignment->target, $assignment->entries_count);
            $this->assertSame($assignment->submitted_count, (int) $assignment->current_progress);
        });

        SurveyEntry::query()->where('entry_status', SurveyEntry::STATUS_SUBMITTED)->pluck('evidence_photo_path')
            ->each(fn (?string $path) => Storage::disk('public')->assertExists($path));

        $this->assertSame(0, Permission::query()->count(), 'Otorisasi hanya memakai peran.');
    }
}
