<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyEntry;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EndToEndModuleConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_survey_changes_are_connected_to_mitra_progress_flow(): void
    {
        $this->seed();
        Storage::fake('public');

        $admin = User::query()->where('email', 'admin@bps.go.id')->firstOrFail();
        $pegawai = User::query()->where('email', 'pegawai@bps.go.id')->firstOrFail();
        $mitra = User::query()->where('email', 'mitra@bps.go.id')->firstOrFail();
        $village = Village::query()->firstOrFail();

        $this->actingAs($admin)->post('/admin/surveys', [
            'type' => Survey::TYPE_PAPI,
            'title' => 'Survei Integrasi Modul',
            'description' => 'Survei untuk memastikan modul pegawai dan mitra terhubung.',
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-30',
        ])->assertRedirect();

        $survey = Survey::query()->where('title', 'Survei Integrasi Modul')->firstOrFail();
        $this->assertSame('Draft', $survey->status);

        $this->actingAs($admin)->post('/admin/surveys/'.$survey->id.'/assignments', [
            'mitra_id' => $mitra->id,
            'village_id' => Village::query()->firstOrFail()->id,
            'sls' => 'RT 001 RW 001',
            'target' => 2,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->actingAs($admin)->get('/admin/surveys/'.$survey->id.'/assignments')
            ->assertOk()
            ->assertSee('Jalankan survei')
            ->assertSee('Checkpoint');

        // Checkpoint adalah langkah opsional setelah alokasi.
        $this->actingAs($admin)->get('/admin/surveys/'.$survey->id.'/checkpoints')
            ->assertOk()
            ->assertSee('Belum ada checkpoint');

        $this->actingAs($admin)->post('/admin/surveys/'.$survey->id.'/finish-setup')
            ->assertRedirect('/admin/surveys')
            ->assertSessionHas('status', 'Survei berhasil dibuat.');

        $survey->refresh();
        $this->assertSame('Berjalan', $survey->status);
        $this->assertSame(2, (int) $survey->total_target);

        $this->actingAs($mitra)->get('/mitra/surveys')
            ->assertOk()
            ->assertSee('Survei Integrasi Modul')
            ->assertSee('/mitra/surveys/'.$survey->id, false);

        // Alokasi manual sudah membuat 2 ruta Open bernomor urut; mitra mengisi ruta pertama.
        $allocated = SurveyEntry::query()->where('survey_id', $survey->id)->orderBy('id')->get();
        $this->assertSame(['1', '2'], $allocated->pluck('no_urut_ruta')->all());
        $this->assertSame([SurveyEntry::STATUS_OPEN], $allocated->pluck('entry_status')->unique()->values()->all());

        $this->actingAs($mitra)->put('/mitra/entries/'.$allocated->first()->id, [
            'action' => 'submit',
            'evidence_photo' => UploadedFile::fake()->image('bukti.jpg'),
            'variables' => [],
        ])->assertRedirect('/mitra/surveys/'.$survey->id)
            ->assertSessionHas('status', 'Entri dikirim. Progres Anda bertambah.');

        $assignment = SurveyAssignment::query()->where('survey_id', $survey->id)->where('mitra_id', $mitra->id)->firstOrFail();
        $this->assertSame(1, (int) $assignment->current_progress);

        $entry = $allocated->first()->fresh();
        $this->assertSame('submitted', $entry->entry_status);

        $this->actingAs($pegawai)->get('/pegawai/entri-papi?survey='.$survey->id)
            ->assertOk()
            ->assertSee('RT 001 RW 001')
            ->assertSee($mitra->name);

        $this->actingAs($pegawai)->put('/admin/surveys/'.$survey->id, [
            'title' => 'Diubah pegawai',
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-30',
        ])->assertForbidden();

        $this->actingAs($pegawai)->get('/pegawai/surveys/'.$survey->id)
            ->assertOk()
            ->assertSee('Survei Integrasi Modul');

        $this->actingAs($admin)->put('/admin/surveys/'.$survey->id, [
            'title' => 'Survei Integrasi Modul Edit',
            'description' => 'Judul diubah oleh admin.',
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-30',
        ])->assertRedirect()
            ->assertSessionHas('status', 'Survei berhasil diperbarui.');

        $this->actingAs($mitra)->get('/mitra/dashboard')
            ->assertOk()
            ->assertSee('Survei Integrasi Modul Edit')
            ->assertDontSee('Survei Integrasi Modul</');
    }
}
