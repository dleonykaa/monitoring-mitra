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
use App\Models\Village;
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

    public function test_admin_prepares_papi_survey_and_pegawai_uses_view_only_menus(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@bps.go.id')->firstOrFail();
        $pegawai = User::query()->where('email', 'pegawai@bps.go.id')->firstOrFail();
        $mitra = User::query()->where('email', 'mitra@bps.go.id')->firstOrFail();

        $this->actingAs($pegawai)->post('/admin/surveys', ['type' => 'papi', 'title' => 'Tidak Boleh'])->assertForbidden();
        $this->actingAs($pegawai)->post('/pegawai/surveys', ['title' => 'Tidak Boleh'])->assertMethodNotAllowed();
        $this->actingAs($pegawai)->get('/pegawai/surveys')->assertOk()->assertDontSee('/pegawai/surveys/create', false);

        $this->actingAs($pegawai)
            ->get('/pegawai/dashboard')
            ->assertOk()
            ->assertSee($pegawai->name)
            ->assertSee('Capaian per survei')
            ->assertSeeInOrder(['Dashboard', 'Monitoring', 'Survei'])
            ->assertDontSee('href="/admin/surveys/create"', false)
            ->assertDontSee('Data Entri</a>', false)
            ->assertDontSee('Daftar Mitra')
            ->assertDontSee('Progress Mitra Survei');

        foreach (['/pegawai/entries', '/pegawai/updates', '/pegawai/mitra', '/pegawai/notifications'] as $removedRoute) {
            $this->actingAs($pegawai)->get($removedRoute)->assertNotFound();
        }

        $existingSurvey = Survey::query()->where('title', 'SUSENAS')->firstOrFail();
        $this->actingAs($pegawai)
            ->get('/pegawai/dashboard')
            ->assertOk()
            ->assertDontSee('href="/pegawai/monitoring/progres?survey='.$existingSurvey->id.'"', false)
            ->assertDontSee('/admin/surveys/create', false);

        $storeResponse = $this->actingAs($admin)->post('/admin/surveys', [
            'type' => 'papi',
            'title' => 'Survei Modul Pegawai',
            'description' => 'Survei untuk uji modul pegawai',
            'total_target' => 1,
            'start_date' => '2026-10-02',
            'end_date' => '2026-10-30',
        ]);

        $survey = Survey::query()->where('title', 'Survei Modul Pegawai')->firstOrFail();
        $storeResponse->assertRedirect('/admin/surveys/'.$survey->id.'/variables');

        $this->actingAs($admin)->post('/admin/surveys/'.$survey->id.'/variables', [
            'name' => 'Jumlah ART',
            'data_type' => 'number',
            'example_format' => '4',
        ])->assertRedirect();

        $this->actingAs($admin)->post('/admin/surveys/'.$survey->id.'/assignments', [
            'mitra_id' => $mitra->id,
            'village_id' => Village::query()->firstOrFail()->id,
            'sls' => 'RT 001 RW 001',
            'target' => 1,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($admin)->get('/admin/surveys/'.$survey->id.'/assignments')
            ->assertOk()
            ->assertSee('Cari dan pilih mitra')
            ->assertSee('Impor dari Excel')
            ->assertDontSee('Tambahkan ke alokasi');

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

        $this->actingAs($pegawai)->get('/pegawai/surveys/'.$survey->id)
            ->assertOk()
            ->assertSee('Data entri')
            ->assertSee('/pegawai/entri-papi?survey='.$survey->id, false)
            ->assertDontSee('Ubah pengaturan')
            ->assertDontSee('Kelola alokasi');
        $this->actingAs($admin)->get('/admin/surveys/'.$survey->id.'/variables/template')->assertOk();
        $this->actingAs($pegawai)->get('/pegawai/surveys/'.$survey->id.'/entries')
            ->assertRedirect('/pegawai/entri-papi?survey='.$survey->id);
        $this->actingAs($pegawai)->get('/pegawai/entri-papi?survey='.$survey->id)
            ->assertOk()
            ->assertSee('Data Entri PAPI')
            ->assertSee('Jumlah ART')
            ->assertSee('RUTA07UJI')
            ->assertSee($mitra->name);
        $this->actingAs($pegawai)->get('/pegawai/entri-papi?survey='.$survey->id.'&q=tidak-ada')
            ->assertOk()
            ->assertSee('Tidak ada entri yang cocok');
        $this->actingAs($pegawai)->get('/pegawai/entri-papi/'.$entry->id)
            ->assertOk()
            ->assertSee('Isian variabel')
            ->assertSee('Jumlah ART')
            ->assertSee('Foto bukti');
        $this->actingAs($pegawai)->get('/pegawai/updates/'.$entry->id)
            ->assertRedirect('/pegawai/entri-papi/'.$entry->id);

        $this->actingAs($admin)->get('/admin/surveys/'.$survey->id.'/variables')
            ->assertOk()
            ->assertSee('Lanjutkan ke Alokasi Mitra')
            ->assertDontSee('Formula Validasi');

        $this->actingAs($pegawai)->get('/pegawai/entri-papi/export?survey='.$survey->id)->assertOk()->assertDownload();
        $this->actingAs($pegawai)->post('/admin/surveys/'.$survey->id.'/status', ['status' => 'Selesai'])->assertForbidden();
        $this->actingAs($admin)
            ->post('/admin/surveys/'.$survey->id.'/status', ['status' => 'Selesai'])
            ->assertRedirect('/admin/surveys/'.$survey->id);
        $this->actingAs($pegawai)->get('/pegawai/surveys')->assertOk()->assertSee('Survei Modul Pegawai');
        $this->assertSame('Selesai', $survey->fresh()->status);
    }

    public function test_dashboard_no_longer_shows_checkpoint_alerts(): void
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
            ->assertDontSee('Alert Mitra Tertinggal Checkpoint')
            ->assertDontSee('Kurang 65 target');
    }
}
