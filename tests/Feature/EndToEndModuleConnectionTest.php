<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EndToEndModuleConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_pegawai_survey_changes_are_connected_to_mitra_progress_flow(): void
    {
        $this->seed();
        Storage::fake('public');

        $pegawai = User::query()->where('email', 'pegawai@bps.go.id')->firstOrFail();
        $mitra = User::query()->where('email', 'mitra@bps.go.id')->firstOrFail();
        $team = $pegawai->teams()->firstOrFail();
        $district = District::query()->firstOrFail();

        $this->actingAs($pegawai)->post('/pegawai/surveys', [
            'team_id' => $team->id,
            'title' => 'Survei Integrasi Modul',
            'description' => 'Survei untuk memastikan modul pegawai dan mitra terhubung.',
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-30',
        ])->assertRedirect();

        $survey = Survey::query()->where('title', 'Survei Integrasi Modul')->firstOrFail();
        $this->assertSame('Draft', $survey->status);

        $this->actingAs($pegawai)->post('/pegawai/surveys/'.$survey->id.'/assignments', [
            'mitra_id' => $mitra->id,
            'target' => 2,
        ])->assertRedirect();

        $this->actingAs($pegawai)->get('/pegawai/surveys/'.$survey->id.'/assignments')
            ->assertOk()
            ->assertSee('Simpan dan Lanjutkan');

        $this->actingAs($pegawai)->get('/pegawai/surveys/'.$survey->id.'/checkpoints')
            ->assertOk()
            ->assertSee('Jalankan Survei');

        $this->actingAs($pegawai)->post('/pegawai/surveys/'.$survey->id.'/checkpoints', [
            'checkpoint_date' => '2026-10-20',
            'target_percentage' => 50,
        ])->assertRedirect();

        $this->assertDatabaseHas('survey_checkpoints', [
            'survey_id' => $survey->id,
            'target_percentage' => 50,
        ]);

        $this->actingAs($pegawai)->post('/pegawai/surveys/'.$survey->id.'/finish-setup')
            ->assertRedirect('/pegawai/surveys')
            ->assertSessionHas('status', 'Survei berhasil dibuat.');

        $survey->refresh();
        $this->assertSame('Berjalan', $survey->status);
        $this->assertSame(2, (int) $survey->total_target);

        $this->actingAs($mitra)->get('/mitra/surveys')
            ->assertOk()
            ->assertSee('Survei Integrasi Modul')
            ->assertSee('Tambah Progress');

        $this->actingAs($mitra)->post('/mitra/surveys/'.$survey->id.'/entries', [
            'action' => 'submit',
            'kode_nks' => 'NKS-INTEGRASI',
            'sls' => 'SLS-INTEGRASI',
            'no_urut_ruta' => 'RUTA-INTEGRASI',
            'district_id' => $district->id,
            'evidence_photo' => UploadedFile::fake()->image('bukti.jpg'),
            'variables' => [],
        ])->assertRedirect('/mitra/surveys/'.$survey->id)
            ->assertSessionHas('status', 'Progress ditambahkan!');

        $assignment = SurveyAssignment::query()->where('survey_id', $survey->id)->where('mitra_id', $mitra->id)->firstOrFail();
        $this->assertSame(1, (int) $assignment->current_progress);

        $entry = SurveyEntry::query()->where('survey_id', $survey->id)->where('no_urut_ruta', 'RUTA-INTEGRASI')->firstOrFail();
        $this->assertSame('submitted', $entry->entry_status);

        $this->actingAs($pegawai)->get('/pegawai/entries?survey_id='.$survey->id)
            ->assertOk()
            ->assertSee('RUTA-INTEGRASI')
            ->assertSee($mitra->name);

        $this->actingAs($pegawai)->put('/pegawai/surveys/'.$survey->id, [
            'title' => 'Survei Integrasi Modul Edit',
            'description' => 'Judul diubah oleh pegawai.',
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-30',
        ])->assertRedirect()
            ->assertSessionHas('status', 'Survei berhasil diperbarui.');

        $this->actingAs($mitra)->get('/mitra/surveys')
            ->assertOk()
            ->assertSee('Survei Integrasi Modul Edit')
            ->assertDontSee('Survei Integrasi Modul</');
    }
}
