<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\SurveyAssignment;
use App\Models\SurveyEntry;
use App\Models\SurveyVariable;
use App\Models\User;
use App\Services\EntryAnomalyValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MitraModuleFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_mitra_can_save_draft_submit_and_edit_pending_entry(): void
    {
        $this->seed();
        Storage::fake('public');

        $mitra = User::query()->where('email', 'mitra@bps.go.id')->firstOrFail();
        $assignment = SurveyAssignment::query()
            ->where('mitra_id', $mitra->id)
            ->whereHas('survey', fn ($query) => $query->where('status', 'Berjalan'))
            ->with('survey.variables')
            ->firstOrFail();
        $district = District::query()->firstOrFail();
        $variable = $assignment->survey->variables->first()
            ?? SurveyVariable::query()->create([
                'survey_id' => $assignment->survey_id,
                'name' => 'Jumlah ART',
                'data_type' => 'number',
                'example_format' => '4',
            ]);
        $variableIdentifier = EntryAnomalyValidator::variableIdentifier($variable->name);
        $assignment->survey->update([
            'validation_rules' => [
                $variableIdentifier.' >= 0',
                $variableIdentifier.' <= 5',
            ],
        ]);

        $initialProgress = $assignment->current_progress;

        $this->actingAs($mitra)->get('/mitra/dashboard')->assertOk()->assertSee('Dashboard Mitra');
        $this->actingAs($mitra)->get('/mitra/surveys')
            ->assertOk()
            ->assertSee($assignment->survey->title)
            ->assertSee('Tambah Progress');
        $this->actingAs($mitra)->get('/mitra/surveys/'.$assignment->survey_id)->assertOk();

        $this->actingAs($mitra)->post('/mitra/surveys/'.$assignment->survey_id.'/entries', [
            'action' => 'draft',
            'no_urut_ruta' => 'RUTA-DRAFT',
            'variables' => [$variable->id => '4'],
        ])->assertRedirect('/mitra/surveys/'.$assignment->survey_id);

        $entry = SurveyEntry::query()->where('no_urut_ruta', 'RUTA-DRAFT')->firstOrFail();
        $this->assertSame('draft', $entry->entry_status);
        $this->assertSame($initialProgress, $assignment->fresh()->current_progress);

        $this->actingAs($mitra)->put('/mitra/entries/'.$entry->id, [
            'action' => 'save_changes',
            'no_urut_ruta' => 'RUTA-DRAFT-REV',
            'district_id' => $district->id,
            'variables' => [$variable->id => '5'],
        ])->assertRedirect('/mitra/surveys/'.$assignment->survey_id);

        $entry->refresh();
        $this->assertSame('draft', $entry->entry_status);
        $this->assertSame($initialProgress, $assignment->fresh()->current_progress);

        $this->actingAs($mitra)->post('/mitra/surveys/'.$assignment->survey_id.'/entries', [
            'action' => 'submit',
            'kode_nks' => 'NKS-SUBMIT',
            'sls' => 'SLS-SUBMIT',
            'no_urut_ruta' => 'RUTA-SUBMIT',
            'district_id' => $district->id,
            'evidence_photo' => UploadedFile::fake()->image('proof.jpg'),
            'variables' => [$variable->id => '9'],
        ])->assertRedirect('/mitra/surveys/'.$assignment->survey_id);

        $entry = SurveyEntry::query()->where('no_urut_ruta', 'RUTA-SUBMIT')->firstOrFail();
        $this->assertSame('submitted', $entry->entry_status);
        $this->assertFalse($entry->is_valid);
        $this->assertNotNull($entry->note);
        $this->assertSame($initialProgress + 1, $assignment->fresh()->current_progress);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $mitra->id,
            'notifiable_type' => User::class,
        ]);

        $this->actingAs($mitra)->get('/mitra/entries')
            ->assertOk()
            ->assertSee('Pilih survei terlebih dahulu')
            ->assertDontSee('RUTA-SUBMIT');
        $this->actingAs($mitra)->get('/mitra/entries?survey_id='.$assignment->survey_id)
            ->assertOk()
            ->assertSee('RUTA-SUBMIT')
            ->assertSee('Tidak Valid');
        $this->actingAs($mitra)->get('/mitra/updates')->assertOk()->assertSee('RUTA-SUBMIT');

        // Mitra memperbaiki nilai yang memicu anomali lalu kirim ulang.
        $this->actingAs($mitra)->put('/mitra/entries/'.$entry->id, [
            'action' => 'submit',
            'kode_nks' => 'NKS-SUBMIT',
            'sls' => 'SLS-SUBMIT',
            'no_urut_ruta' => 'RUTA-REVISI',
            'district_id' => $district->id,
            'variables' => [$variable->id => '5'],
        ])->assertRedirect('/mitra/surveys/'.$assignment->survey_id);

        $entry->refresh();
        $this->assertSame('RUTA-REVISI', $entry->no_urut_ruta);
        $this->assertSame('submitted', $entry->entry_status);
        $this->assertTrue($entry->is_valid);
        $this->assertNull($entry->note);
        $this->assertSame($initialProgress + 1, $assignment->fresh()->current_progress);

        $pegawai = User::query()->where('email', 'pegawai@bps.go.id')->firstOrFail();
        $this->actingAs($pegawai)->get('/pegawai/updates/'.$entry->id)
            ->assertOk()
            ->assertSee('Valid')
            ->assertDontSee('Hubungi Mitra');
    }
}
