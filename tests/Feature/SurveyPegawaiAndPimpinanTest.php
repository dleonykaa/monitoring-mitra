<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyEntry;
use App\Models\User;
use App\Models\Village;
use App\Notifications\NewEntrySubmittedNotification;
use App\Notifications\PegawaiAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SurveyPegawaiAndPimpinanTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_assigns_and_removes_pegawai_as_survey_supervisor(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@bps.go.id')->firstOrFail();
        $pegawai = $this->user('pegawai_bps', 'Rina Pengawas');
        $survey = $this->papiSurvey($admin, 'Pengawasan Uji', 'Berjalan');

        $this->actingAs($admin)->get('/admin/surveys/'.$survey->id)
            ->assertOk()
            ->assertSee('Pegawai penanggung jawab')
            ->assertSee('Belum ada pegawai yang ditugaskan.')
            ->assertSee('<option value="'.$pegawai->id.'"', false);

        $this->actingAs($admin)->post('/admin/surveys/'.$survey->id.'/pegawai', ['user_id' => $pegawai->id])
            ->assertSessionHasNoErrors();

        $this->assertTrue($survey->pegawai()->whereKey($pegawai->id)->exists());
        $notification = $pegawai->notifications()->sole();
        $this->assertSame(PegawaiAssignedNotification::class, $notification->type);
        $this->assertSame($survey->id, $notification->data['survey_id']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'admin.survey.pegawai_assign']);

        $this->actingAs($pegawai)->get('/pegawai/dashboard')
            ->assertOk()
            ->assertSee('href="/pegawai/surveys/'.$survey->id.'"', false);
        $this->actingAs($admin)->get('/admin/surveys/'.$survey->id)
            ->assertOk()
            ->assertSee('Rina Pengawas')
            ->assertDontSee('<option value="'.$pegawai->id.'"', false);

        // Penugasan ganda, akun non-pegawai, dan akun nonaktif ditolak.
        $this->actingAs($admin)->post('/admin/surveys/'.$survey->id.'/pegawai', ['user_id' => $pegawai->id])
            ->assertSessionHasErrors('user_id');
        $this->actingAs($admin)->post('/admin/surveys/'.$survey->id.'/pegawai', ['user_id' => $this->user('mitra')->id])
            ->assertStatus(422);
        $this->actingAs($admin)->post('/admin/surveys/'.$survey->id.'/pegawai', ['user_id' => $this->user('pegawai_bps', active: false)->id])
            ->assertSessionHasErrors('user_id');

        // Hanya admin yang boleh mengatur penanggung jawab.
        $this->actingAs($pegawai)->post('/admin/surveys/'.$survey->id.'/pegawai', ['user_id' => $pegawai->id])->assertForbidden();
        $this->actingAs($pegawai)->delete('/admin/surveys/'.$survey->id.'/pegawai/'.$pegawai->id)->assertForbidden();

        $this->actingAs($admin)->delete('/admin/surveys/'.$survey->id.'/pegawai/'.$pegawai->id)->assertSessionHasNoErrors();
        $this->assertFalse($survey->pegawai()->exists());
        $this->actingAs($admin)->delete('/admin/surveys/'.$survey->id.'/pegawai/'.$pegawai->id)->assertNotFound();

        // Survei selesai terkunci.
        $survey->update(['status' => 'Selesai']);
        $this->actingAs($admin)->post('/admin/surveys/'.$survey->id.'/pegawai', ['user_id' => $pegawai->id])->assertForbidden();
        $this->actingAs($admin)->get('/admin/surveys/'.$survey->id)->assertOk()->assertDontSee('Tugaskan');
    }

    public function test_pegawai_sees_every_survey_with_own_surveys_marked_and_opened_by_default(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@bps.go.id')->firstOrFail();
        $pegawai = $this->user('pegawai_bps');
        $mine = $this->papiSurvey($admin, 'Survei Tanggung Jawab Saya', 'Berjalan');
        $other = $this->papiSurvey($admin, 'Survei Pegawai Lain', 'Berjalan');
        $mine->pegawai()->attach($pegawai->id);

        $this->actingAs($pegawai)->get('/pegawai/surveys')
            ->assertOk()
            ->assertSee('aria-label="Filter penanggung jawab"', false)
            ->assertSee('Survei Tanggung Jawab Saya')
            ->assertSee('Survei Pegawai Lain')
            ->assertSee('data-mine="1"', false);

        // Monitoring dan data entri langsung membuka survei miliknya walau bukan survei terbaru.
        $this->actingAs($pegawai)->get('/pegawai/monitoring/progres')
            ->assertOk()
            ->assertViewHas('selectedSurvey', fn (?Survey $survey): bool => $survey?->is($mine) ?? false)
            ->assertSee('Survei Tanggung Jawab Saya · PAPI · Survei saya');
        $this->actingAs($pegawai)->get('/pegawai/entri-papi')
            ->assertOk()
            ->assertViewHas('survey', fn (?Survey $survey): bool => $survey?->is($mine) ?? false);

        // Survei lain tetap bisa dibuka.
        $this->actingAs($pegawai)->get('/pegawai/monitoring/progres?survey='.$other->id)
            ->assertOk()
            ->assertViewHas('selectedSurvey', fn (?Survey $survey): bool => $survey?->is($other) ?? false);
        $this->actingAs($pegawai)->get('/pegawai/surveys/'.$other->id)->assertOk();

        // Pegawai tanpa penugasan tidak melihat filter "Survei saya".
        $this->actingAs($this->user('pegawai_bps'))->get('/pegawai/surveys')
            ->assertOk()
            ->assertDontSee('aria-label="Filter penanggung jawab"', false);
    }

    public function test_entry_notification_goes_to_admin_and_assigned_pegawai_only(): void
    {
        $this->seed();
        Storage::fake(SurveyEntry::PHOTO_DISK);

        $admin = User::query()->where('email', 'admin@bps.go.id')->firstOrFail();
        $pimpinan = User::query()->where('email', 'pimpinan@bps.go.id')->firstOrFail();
        $assigned = $this->user('pegawai_bps');
        $otherPegawai = $this->user('pegawai_bps');
        $mitra = $this->user('mitra');

        $survey = $this->papiSurvey($admin, 'Survei Notifikasi PML', 'Berjalan');
        $survey->pegawai()->attach($assigned->id);
        $entry = $this->openEntry($survey, $mitra);

        $this->actingAs($mitra)->put('/mitra/entries/'.$entry->id, [
            'action' => 'submit',
            'evidence_photo' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertSessionHasNoErrors();

        foreach ([$admin, $assigned] as $staff) {
            $this->assertSame(NewEntrySubmittedNotification::class, $staff->notifications()->sole()->type);
        }
        $this->assertSame(0, $otherPegawai->notifications()->count());
        $this->assertSame(0, $pimpinan->notifications()->count());

        // Survei tanpa penanggung jawab tetap memberi tahu seluruh pegawai.
        $unassigned = $this->papiSurvey($admin, 'Survei Tanpa PML', 'Berjalan');
        $secondEntry = $this->openEntry($unassigned, $mitra);
        $this->actingAs($mitra)->put('/mitra/entries/'.$secondEntry->id, [
            'action' => 'submit',
            'evidence_photo' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, $otherPegawai->notifications()->count());
        $this->assertSame(2, $assigned->notifications()->count());
        $this->assertSame(0, $pimpinan->notifications()->count());
    }

    public function test_pimpinan_uses_a_view_only_panel(): void
    {
        $this->seed();
        Storage::fake(SurveyEntry::PHOTO_DISK);

        $admin = User::query()->where('email', 'admin@bps.go.id')->firstOrFail();
        $pimpinan = User::query()->where('email', 'pimpinan@bps.go.id')->firstOrFail();
        $survey = $this->papiSurvey($admin, 'Survei Pantauan Pimpinan', 'Berjalan');
        $entry = $this->openEntry($survey, $this->user('mitra'));
        $entry->update(['evidence_photo_path' => 'bukti/foto.jpg']);
        Storage::disk(SurveyEntry::PHOTO_DISK)->put('bukti/foto.jpg', 'foto');

        $this->post('/login', ['email' => 'pimpinan@bps.go.id', 'password' => 'password123'])
            ->assertRedirect('/pimpinan/dashboard');

        $this->actingAs($pimpinan)->get('/pimpinan/dashboard')
            ->assertOk()
            ->assertSee('Pimpinan')
            ->assertSee('href="/pimpinan/monitoring/progres"', false)
            ->assertDontSee('href="/admin/surveys/create"', false);

        foreach (['/pimpinan/surveys', '/pimpinan/surveys/'.$survey->id, '/pimpinan/monitoring/progres', '/pimpinan/entri-papi', '/pimpinan/entri-papi/'.$entry->id, '/pimpinan/profile', '/bukti/'.$entry->id] as $url) {
            $this->actingAs($pimpinan)->get($url)->assertOk();
        }
        $this->actingAs($pimpinan)->get('/pimpinan/surveys/'.$survey->id)
            ->assertSee('Pegawai penanggung jawab')
            ->assertDontSee('Tugaskan');

        foreach (['/admin/dashboard', '/pegawai/dashboard', '/mitra/dashboard'] as $url) {
            $this->actingAs($pimpinan)->get($url)->assertForbidden();
        }
        $this->actingAs($pimpinan)->post('/admin/surveys', ['type' => 'papi', 'title' => 'Tidak Boleh'])->assertForbidden();
        $this->actingAs($this->user('pegawai_bps'))->get('/pimpinan/dashboard')->assertForbidden();
    }

    public function test_admin_manages_pimpinan_accounts_and_role_change_clears_supervision(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@bps.go.id')->firstOrFail();

        $this->actingAs($admin)->get('/admin/users?role=pimpinan')
            ->assertOk()
            ->assertSee('Kepala BPS')
            ->assertSee('Tambah Pimpinan');

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Wakil Pimpinan',
            'email' => 'wakil@bps.go.id',
            'password' => 'password123',
            'role' => 'pimpinan',
        ])->assertSessionHasNoErrors();
        $this->assertTrue(User::query()->where('email', 'wakil@bps.go.id')->firstOrFail()->hasRole('pimpinan'));

        $pegawai = $this->user('pegawai_bps');
        $survey = $this->papiSurvey($admin, 'Survei Ganti Peran', 'Berjalan');
        $survey->pegawai()->attach($pegawai->id);

        $this->actingAs($admin)->put('/admin/users/'.$pegawai->id, [
            'name' => $pegawai->name,
            'email' => $pegawai->email,
            'is_active' => 1,
            'role' => 'pimpinan',
        ])->assertSessionHasNoErrors();

        $this->assertTrue($pegawai->fresh()->hasRole('pimpinan'));
        $this->assertFalse($survey->pegawai()->exists());
    }

    private function user(string $role, string $name = 'Pengguna Uji', bool $active = true): User
    {
        $user = User::factory()->create(['name' => $name, 'is_active' => $active]);
        $user->assignRole($role);

        return $user;
    }

    private function papiSurvey(User $admin, string $title, string $status): Survey
    {
        return Survey::query()->create([
            'type' => Survey::TYPE_PAPI,
            'title' => $title,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'status' => $status,
            'created_by' => $admin->id,
            'total_target' => 1,
        ]);
    }

    private function openEntry(Survey $survey, User $mitra): SurveyEntry
    {
        $village = Village::query()->firstOrFail();
        $assignment = SurveyAssignment::query()->create(['survey_id' => $survey->id, 'mitra_id' => $mitra->id, 'target' => 1]);

        return SurveyEntry::query()->create([
            'survey_id' => $survey->id,
            'survey_assignment_id' => $assignment->id,
            'district_id' => $village->district_id,
            'village_id' => $village->id,
            'sls' => '0001',
            'no_urut_ruta' => '1',
            'entry_status' => SurveyEntry::STATUS_OPEN,
        ]);
    }
}
