<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyEntry;
use App\Models\SurveyVariable;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MitraModuleFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_mitra_fills_entry_from_open_to_draft_to_selesai(): void
    {
        $this->seed();
        Storage::fake('public');

        $mitra = User::query()->where('email', 'mitra@bps.go.id')->firstOrFail();
        $assignment = SurveyAssignment::query()
            ->where('mitra_id', $mitra->id)
            ->whereHas('survey', fn ($query) => $query->where('status', 'Berjalan'))
            ->with('survey.variables')
            ->firstOrFail();
        $assignment->update(['target' => $assignment->entries()->count() + 3]);
        $survey = $assignment->survey;
        if ($survey->variables->isEmpty()) {
            SurveyVariable::query()->create([
                'survey_id' => $survey->id,
                'name' => 'Jumlah ART',
                'data_type' => 'number',
                'example_format' => '4',
            ]);
            $survey->load('variables');
        }
        $village = Village::query()->firstOrFail();
        $allValues = $survey->variables->mapWithKeys(fn (SurveyVariable $variable): array => [
            $variable->id => $variable->data_type === 'number' ? '4' : 'Terisi',
        ])->all();
        $firstVariable = $survey->variables->first();

        $openEntry = SurveyEntry::query()->create([
            'survey_id' => $survey->id,
            'survey_assignment_id' => $assignment->id,
            'district_id' => $village->district_id,
            'village_id' => $village->id,
            'kode_nks' => 'NKS-OPEN',
            'sls' => '0001',
            'ppl' => $mitra->name,
            'no_urut_ruta' => 'RUTA-OPEN',
            'entry_status' => SurveyEntry::STATUS_OPEN,
        ]);
        $initialProgress = $assignment->current_progress;

        $this->actingAs($mitra)->get('/mitra/dashboard')
            ->assertOk()
            ->assertSeeInOrder(['Dashboard', 'Daftar Survei', 'Data Entri'])
            ->assertSee('Survei berjalan')
            ->assertSee($survey->title)
            // Dashboard hanya ringkasan: tidak ada tombol menuju pengisian.
            ->assertDontSee('href="/mitra/surveys/'.$survey->id.'"', false)
            ->assertDontSee('Update progress');
        $this->actingAs($mitra)->get('/mitra/progress')->assertRedirect('/mitra/surveys');
        $this->actingAs($mitra)->get('/mitra/surveys')
            ->assertOk()
            ->assertSee($survey->title)
            ->assertSee('href="/mitra/surveys/'.$survey->id.'"', false)
            ->assertSee('Update progress');
        $this->actingAs($mitra)->get('/mitra/surveys?status=Selesai')->assertOk()->assertDontSee($survey->title);
        $this->actingAs($mitra)->get('/mitra/surveys/'.$survey->id)
            ->assertOk()
            ->assertSee('Isi ruta baru')
            ->assertSee('RUTA-OPEN')
            ->assertSee('Mulai isi');
        $this->actingAs($mitra)->get('/mitra/entries/'.$openEntry->id.'/edit')
            ->assertOk()
            ->assertSee('Foto bukti')
            ->assertSee($firstVariable->name)
            ->assertSee('Simpan draft')
            ->assertSee('Kirim');

        // Isian sebagian disimpan sebagai draft.
        $this->actingAs($mitra)->put('/mitra/entries/'.$openEntry->id, [
            'action' => 'draft',
            'no_urut_ruta' => 'RUTA-OPEN',
            'variables' => [$firstVariable->id => '4'],
        ])->assertRedirect('/mitra/surveys/'.$survey->id);

        $openEntry->refresh();
        $this->assertSame(SurveyEntry::STATUS_DRAFT, $openEntry->entry_status);
        $this->assertSame($initialProgress, $assignment->fresh()->current_progress);

        // Draft yang sudah berisi nilai variabel bisa dibuka lagi dengan isian sebelumnya.
        $this->actingAs($mitra)->get('/mitra/entries/'.$openEntry->id.'/edit')
            ->assertOk()
            ->assertSee('value="4"', false);

        // Selesai ditolak bila isian atau foto bukti belum lengkap.
        $this->actingAs($mitra)->put('/mitra/entries/'.$openEntry->id, [
            'action' => 'submit',
            'no_urut_ruta' => 'RUTA-OPEN',
            'variables' => [$firstVariable->id => '4'],
        ])->assertSessionHasErrors(['evidence_photo'])
            // Identitas ruta hasil alokasi tetap dari admin, jadi tidak ikut divalidasi ulang.
            ->assertSessionDoesntHaveErrors(['district_id', 'village_id', 'sls', 'no_urut_ruta']);
        $this->assertSame(SurveyEntry::STATUS_DRAFT, $openEntry->fresh()->entry_status);

        $this->actingAs($mitra)->put('/mitra/entries/'.$openEntry->id, [
            'action' => 'submit',
            'district_id' => $village->district_id,
            'village_id' => $village->id,
            'kode_nks' => 'NKS-OPEN',
            'sls' => '0001',
            'no_urut_ruta' => 'RUTA-OPEN',
            'evidence_photo' => UploadedFile::fake()->image('bukti.jpg'),
            'variables' => $allValues,
        ])->assertRedirect('/mitra/surveys/'.$survey->id);

        $openEntry->refresh();
        $this->assertSame(SurveyEntry::STATUS_SUBMITTED, $openEntry->entry_status);
        $this->assertNotNull($openEntry->submitted_at);
        Storage::disk('public')->assertExists($openEntry->evidence_photo_path);
        $this->assertSame($initialProgress + 1, $assignment->fresh()->current_progress);

        // Entri selesai terkunci.
        $this->actingAs($mitra)->get('/mitra/entries/'.$openEntry->id.'/edit')->assertForbidden();

        // Entri baru dari sisa target langsung diselesaikan.
        $this->actingAs($mitra)->post('/mitra/surveys/'.$survey->id.'/entries', [
            'action' => 'submit',
            'district_id' => $village->district_id,
            'village_id' => $village->id,
            'kode_nks' => 'NKS-BARU',
            'sls' => '0002',
            'no_urut_ruta' => 'RUTA-BARU',
            'evidence_photo' => UploadedFile::fake()->image('bukti-baru.jpg'),
            'variables' => $allValues,
        ])->assertRedirect('/mitra/surveys/'.$survey->id);

        $newEntry = SurveyEntry::query()->where('no_urut_ruta', 'RUTA-BARU')->firstOrFail();
        $this->assertSame(SurveyEntry::STATUS_SUBMITTED, $newEntry->entry_status);
        $this->assertSame($initialProgress + 2, $assignment->fresh()->current_progress);

        $this->actingAs($mitra)->get('/mitra/surveys/'.$survey->id)
            ->assertOk()
            ->assertSee('RUTA-BARU')
            ->assertSee('Terkirim');
        $this->actingAs($mitra)->get('/mitra/entries')->assertRedirect('/mitra/data-entri');

        // Data Entri hanya memuat entri terkirim dan hanya bisa dilihat.
        $draftEntry = SurveyEntry::query()->create([
            'survey_id' => $survey->id,
            'survey_assignment_id' => $assignment->id,
            'no_urut_ruta' => 'RUTA-DRAFT',
            'entry_status' => SurveyEntry::STATUS_DRAFT,
        ]);
        $this->actingAs($mitra)->get('/mitra/data-entri?survey='.$survey->id.'&q=RUTA-')
            ->assertOk()
            ->assertSee('RUTA-BARU')
            ->assertSee('RUTA-OPEN')
            ->assertDontSee('RUTA-DRAFT')
            ->assertSee('/mitra/data-entri/'.$newEntry->id, false);
        $this->actingAs($mitra)->get('/mitra/data-entri/'.$newEntry->id)
            ->assertOk()
            ->assertSee('RUTA-BARU')
            ->assertDontSee('/edit', false);
        $this->actingAs($mitra)->get('/mitra/data-entri/'.$draftEntry->id)->assertNotFound();
        $this->actingAs($mitra)->get('/mitra/entries/'.$newEntry->id.'/edit')->assertForbidden();

        $otherMitra = User::factory()->create(['is_active' => true]);
        $otherMitra->assignRole('mitra');
        $this->actingAs($otherMitra)->get('/mitra/data-entri/'.$newEntry->id)->assertForbidden();
        $this->actingAs($otherMitra)->get('/mitra/data-entri')->assertOk()->assertDontSee('RUTA-BARU');

        $pegawai = User::query()->where('email', 'pegawai@bps.go.id')->firstOrFail();
        $this->actingAs($pegawai)->get('/pegawai/entri-papi/'.$newEntry->id)
            ->assertOk()
            ->assertSee('Selesai')
            ->assertSee('Foto bukti pencacahan ruta RUTA-BARU');
    }

    public function test_number_variable_rejects_text_with_form_error(): void
    {
        $this->seed();

        $mitra = User::query()->where('email', 'mitra@bps.go.id')->firstOrFail();
        $assignment = SurveyAssignment::query()
            ->where('mitra_id', $mitra->id)
            ->whereHas('survey', fn ($query) => $query->where('status', 'Berjalan')->where('type', 'papi'))
            ->firstOrFail();
        $assignment->update(['target' => $assignment->entries()->count() + 1]);
        $variable = SurveyVariable::query()->create([
            'survey_id' => $assignment->survey_id,
            'name' => 'Jumlah Anggota',
            'data_type' => 'number',
            'example_format' => '4',
        ]);

        $this->actingAs($mitra)->from('/mitra/surveys/'.$assignment->survey_id.'/entries/create')
            ->post('/mitra/surveys/'.$assignment->survey_id.'/entries', [
                'action' => 'draft',
                'variables' => [$variable->id => 'empat'],
            ])
            ->assertRedirect('/mitra/surveys/'.$assignment->survey_id.'/entries/create')
            ->assertSessionHasErrors('variables.'.$variable->id);
    }

    public function test_mitra_sees_only_running_surveys_and_cannot_touch_draft_or_capi(): void
    {
        $this->seed();

        $mitra = User::query()->where('email', 'mitra@bps.go.id')->firstOrFail();
        $admin = User::query()->where('email', 'admin@bps.go.id')->firstOrFail();
        $draft = Survey::query()->create([
            'type' => 'papi',
            'title' => 'Survei Draft Rahasia',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'Draft',
            'created_by' => $admin->id,
            'total_target' => 3,
        ]);
        $draft->assignments()->create(['mitra_id' => $mitra->id, 'target' => 3]);

        $this->actingAs($mitra)->get('/mitra/dashboard')->assertOk()->assertDontSee('Survei Draft Rahasia');
        $this->actingAs($mitra)->get('/mitra/surveys')->assertOk()->assertDontSee('Survei Draft Rahasia');
        $this->actingAs($mitra)->get('/mitra/surveys/'.$draft->id)->assertNotFound();
        $this->actingAs($mitra)->get('/pegawai/dashboard')->assertForbidden();
        $this->actingAs($mitra)->get('/admin/mitra')->assertForbidden();
    }

    public function test_mitra_cannot_add_entry_beyond_target(): void
    {
        $this->seed();

        $mitra = User::query()->where('email', 'mitra@bps.go.id')->firstOrFail();
        $assignment = SurveyAssignment::query()
            ->where('mitra_id', $mitra->id)
            ->whereHas('survey', fn ($query) => $query->where('status', 'Berjalan'))
            ->firstOrFail();
        $entryCount = $assignment->entries()->count();
        if ($entryCount === 0) {
            SurveyEntry::query()->create([
                'survey_id' => $assignment->survey_id,
                'survey_assignment_id' => $assignment->id,
                'entry_status' => SurveyEntry::STATUS_OPEN,
            ]);
            $entryCount = 1;
        }
        $assignment->update(['target' => $entryCount]);

        $this->actingAs($mitra)->get('/mitra/surveys/'.$assignment->survey_id)
            ->assertOk()
            ->assertDontSee('Isi ruta baru');
        $this->actingAs($mitra)->get('/mitra/surveys/'.$assignment->survey_id.'/entries/create')
            ->assertRedirect('/mitra/surveys/'.$assignment->survey_id)
            ->assertSessionHasErrors('entry');
        $this->actingAs($mitra)->post('/mitra/surveys/'.$assignment->survey_id.'/entries', ['action' => 'draft'])
            ->assertSessionHasErrors('entry');
        $this->assertSame($entryCount, $assignment->entries()->count());
    }
}
