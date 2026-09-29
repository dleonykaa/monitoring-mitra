<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\EntryVariableValue;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyCheckpoint;
use App\Models\SurveyEntry;
use App\Models\SurveyVariable;
use App\Models\User;
use App\Services\EntryAnomalyValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PegawaiModuleFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_redirects_each_role_to_the_correct_home(): void
    {
        $this->seed();

        $this->post('/login', ['email' => 'admin@bps.go.id', 'password' => 'password123'])
            ->assertRedirect('/admin/dashboard');
        $this->post('/logout');

        $this->post('/login', ['email' => 'pegawai@bps.go.id', 'password' => 'password123'])
            ->assertRedirect('/pegawai/dashboard');
        $this->post('/logout');

        $this->post('/login', ['email' => 'mitra@bps.go.id', 'password' => 'password123'])
            ->assertRedirect('/mitra/dashboard');
    }

    public function test_pegawai_can_use_survey_wizard_and_core_menus(): void
    {
        $this->seed();

        $pegawai = User::query()->where('email', 'pegawai@bps.go.id')->firstOrFail();
        $mitra = User::query()->where('email', 'mitra@bps.go.id')->firstOrFail();
        $team = $pegawai->teams()->firstOrFail();

        $this->actingAs($pegawai)
            ->get('/pegawai/dashboard')
            ->assertOk()
            ->assertSee('Hi, '.$pegawai->name)
            ->assertSee('Progress Survei')
            ->assertDontSee('Progress Mitra Survei');

        $existingSurvey = Survey::query()->where('title', 'SUSENAS')->firstOrFail();
        $this->actingAs($pegawai)
            ->get('/pegawai/dashboard?survey_id='.$existingSurvey->id)
            ->assertOk()
            ->assertSee('Progress Mitra Survei')
            ->assertSee('Cari mitra')
            ->assertSee('Sebelumnya')
            ->assertSee('Berikutnya');

        $storeResponse = $this->actingAs($pegawai)->post('/pegawai/surveys', [
            'team_id' => $team->id,
            'title' => 'Survei Modul Pegawai',
            'description' => 'Survei untuk uji modul pegawai',
            'total_target' => 1,
            'start_date' => '2026-10-02',
            'end_date' => '2026-10-30',
        ]);

        $survey = Survey::query()->where('title', 'Survei Modul Pegawai')->firstOrFail();
        $storeResponse->assertRedirect('/pegawai/surveys/'.$survey->id.'/variables');

        $this->actingAs($pegawai)->post('/pegawai/surveys/'.$survey->id.'/variables', [
            'name' => 'Jumlah ART',
            'data_type' => 'number',
            'example_format' => '4',
        ])->assertRedirect();

        $this->actingAs($pegawai)->post('/pegawai/surveys/'.$survey->id.'/assignments', [
            'mitra_id' => $mitra->id,
            'target' => 1,
        ])->assertRedirect();
        $this->actingAs($pegawai)->get('/pegawai/surveys/'.$survey->id.'/assignments')
            ->assertOk()
            ->assertSee('Cari dan pilih mitra')
            ->assertSee('Tambahkan sebagai alokasi baru')
            ->assertSee('Ganti alokasi yang sudah ada');

        $assignment = SurveyAssignment::query()->where('survey_id', $survey->id)->where('mitra_id', $mitra->id)->firstOrFail();
        $district = District::query()->firstOrFail();
        $variable = SurveyVariable::query()->where('survey_id', $survey->id)->firstOrFail();
        $entry = SurveyEntry::query()->create([
            'survey_id' => $survey->id,
            'survey_assignment_id' => $assignment->id,
            'district_id' => $district->id,
            'kode_nks' => 'NKSUJI01',
            'sls' => 'SLSUJI',
            'ppl' => $mitra->name,
            'no_urut_ruta' => 'RUTA07UJI',
            'evidence_photo_path' => 'survey-evidence/proof.jpg',
        ]);
        EntryVariableValue::query()->create([
            'survey_entry_id' => $entry->id,
            'survey_variable_id' => $variable->id,
            'value' => '4',
        ]);
        $assignment->update(['current_progress' => 1]);

        $this->actingAs($pegawai)->get('/pegawai/surveys/'.$survey->id)->assertOk()->assertSee('Detail Survei');
        $this->actingAs($pegawai)->get('/pegawai/surveys/'.$survey->id.'/variables/template')->assertOk();
        $this->actingAs($pegawai)->get('/pegawai/updates')->assertOk()->assertSee('Riwayat Update');
        $this->actingAs($pegawai)->get('/pegawai/updates?modified_from=2026-01-01&modified_to=2026-12-31')
            ->assertOk()
            ->assertSee('Pilih Rentang Waktu')
            ->assertSee('RUTA07UJI');
        $this->actingAs($pegawai)->get('/pegawai/updates/'.$entry->id)
            ->assertOk()
            ->assertSee('Dokumentasi')
            ->assertSee('Jumlah ART');
        $this->actingAs($pegawai)->get('/pegawai/mitra')->assertOk()->assertSee($mitra->name);
        $this->actingAs($pegawai)->get('/pegawai/entries?survey_id='.$survey->id)
            ->assertOk()
            ->assertSee('Submit')
            ->assertSee('Modified')
            ->assertSee('#1')
            ->assertSee('Detail')
            ->assertDontSee('Catatan internal')
            ->assertSee('RUTA07UJI');
        $this->actingAs($pegawai)->get('/pegawai/entries?survey_id='.$survey->id.'&modified_from=2026-01-01&modified_to=2026-12-31')
            ->assertOk()
            ->assertSee('Pilih Rentang Waktu')
            ->assertSee('RUTA07UJI');

        $identifier = EntryAnomalyValidator::variableIdentifier($variable->name);
        $this->actingAs($pegawai)->put('/pegawai/surveys/'.$survey->id.'/validation-formula', [
            'validation_rules' => [
                $identifier.' >= 0',
                $identifier.' <= 5',
                '',
            ],
        ])->assertRedirect();
        $this->assertSame([$identifier.' >= 0', $identifier.' <= 5'], $survey->fresh()->validation_rules);
        $this->actingAs($pegawai)->get('/pegawai/surveys/'.$survey->id.'/variables')
            ->assertOk()
            ->assertSee('Formula Validasi')
            ->assertSee($identifier.' >= 0')
            ->assertSee($identifier.' <= 5');

        $this->actingAs($pegawai)->get('/pegawai/entries/export?survey_id='.$survey->id.'&format=xlsx')->assertOk();
        $this->actingAs($pegawai)->get('/pegawai/entries/export?survey_id='.$survey->id.'&format=csv')->assertOk();
        $this->actingAs($pegawai)
            ->post('/pegawai/surveys/'.$survey->id.'/status', ['status' => 'Selesai'])
            ->assertRedirect('/pegawai/surveys/'.$survey->id);
        $this->actingAs($pegawai)->get('/pegawai/surveys')->assertOk();
        $this->assertSame('Selesai', $survey->fresh()->status);
    }

    public function test_dashboard_alerts_mitra_who_are_far_behind_a_due_checkpoint(): void
    {
        $this->seed();

        $pegawai = User::query()->where('email', 'pegawai@bps.go.id')->firstOrFail();
        $survey = Survey::query()->where('title', 'SUSENAS')->firstOrFail();
        $assignment = SurveyAssignment::query()->where('survey_id', $survey->id)->firstOrFail();

        $assignment->update([
            'target' => 100,
            'current_progress' => 10,
        ]);
        SurveyCheckpoint::query()
            ->where('survey_id', $survey->id)
            ->update(['checkpoint_date' => now()->subDay()->toDateString()]);
        SurveyCheckpoint::query()->create([
            'survey_id' => $survey->id,
            'checkpoint_date' => now()->toDateString(),
            'target_percentage' => 75,
        ]);

        $this->actingAs($pegawai)
            ->get('/pegawai/dashboard?survey_id='.$survey->id)
            ->assertOk()
            ->assertSee('Alert Mitra Tertinggal Checkpoint')
            ->assertSee($assignment->mitra->name)
            ->assertSee('Kurang 65 target');
    }
}
