<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminModuleFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_core_admin_features(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@bps.go.id')->firstOrFail();
        $pegawai = User::query()->where('email', 'pegawai@bps.go.id')->firstOrFail();
        $mitra = User::query()->role('mitra')->firstOrFail();

        foreach (['/admin/dashboard', '/admin/monitoring/progres', '/admin/surveys', '/admin/mitra', '/admin/users', '/admin/logs'] as $route) {
            $this->actingAs($admin)->get($route)->assertOk();
        }

        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertSeeInOrder(['Dashboard', 'Monitoring', 'Survei', 'Daftar Mitra', 'Admin', 'Manajemen Pengguna', 'Sistem', 'Log Aktivitas'])
            ->assertDontSee('Role &amp; Hak Akses', false)
            ->assertDontSee('href="/admin/monitoring/kinerja"', false);

        foreach (['/admin/roles', '/admin/teams', '/admin/regions'] as $removedRoute) {
            $this->actingAs($admin)->get($removedRoute)->assertNotFound();
        }
        $this->actingAs($admin)->get('/admin/monitoring/kinerja')->assertRedirect('/admin/mitra');

        $this->actingAs($admin)->get('/admin/users?role=pegawai_bps')
            ->assertOk()
            ->assertSee($pegawai->email)
            ->assertDontSee($mitra->email);

        $this->actingAs($admin)->get('/admin/users?role=mitra')
            ->assertOk()
            ->assertSee($mitra->email)
            ->assertDontSee($pegawai->email);

        $this->actingAs($admin)->get('/admin/users?role=admin')
            ->assertOk()
            ->assertSee($admin->email)
            ->assertSee('Tambah Admin');

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Admin Test Mitra',
            'email' => 'admin.test.mitra@example.com',
            'password' => 'password123',
            'role' => 'mitra',
        ])->assertRedirect();

        $user = User::query()->where('email', 'admin.test.mitra@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('mitra'));

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Peran Tidak Dikenal',
            'email' => 'peran.lain@example.com',
            'password' => 'password123',
            'role' => 'supervisor',
        ])->assertSessionHasErrors('role');

        $this->actingAs($admin)->put('/admin/users/'.$user->id, [
            'name' => 'Admin Test Pegawai',
            'email' => 'admin.test.pegawai@example.com',
            'is_active' => 0,
            'role' => 'pegawai_bps',
            'password' => null,
        ])->assertRedirect();

        $user->refresh();
        $this->assertFalse($user->is_active);
        $this->assertTrue($user->hasRole('pegawai_bps'));

        $this->actingAs($admin)->get('/admin/logs?action=admin')->assertOk();
        $this->actingAs($admin)->delete('/admin/users/'.$user->id)->assertRedirect();
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_admin_mitra_directory_lists_surveys_held_without_rating(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@bps.go.id')->firstOrFail();
        $mitra = User::query()->where('email', 'mitra@bps.go.id')->firstOrFail();
        $susenas = Survey::query()->where('title', 'SUSENAS')->firstOrFail();
        $finished = Survey::query()->create([
            'type' => Survey::TYPE_PAPI,
            'title' => 'Survei Selesai Mitra',
            'start_date' => now()->subMonths(2)->toDateString(),
            'end_date' => now()->subMonth()->toDateString(),
            'status' => 'Selesai',
            'created_by' => $admin->id,
            'total_target' => 5,
        ]);
        $finished->assignments()->create(['mitra_id' => $mitra->id, 'target' => 5, 'current_progress' => 5]);

        $this->actingAs($admin)->get('/admin/mitra')
            ->assertOk()
            ->assertSee('Daftar mitra')
            ->assertSee($mitra->name)
            ->assertSee('/admin/mitra/'.$mitra->id, false)
            ->assertDontSee('Kategori');

        $this->actingAs($admin)->get('/admin/mitra?q='.urlencode($mitra->email))
            ->assertOk()
            ->assertSee($mitra->name);

        $this->actingAs($admin)->get('/admin/mitra/'.$mitra->id)
            ->assertOk()
            ->assertSeeInOrder(['Survei berjalan', $susenas->title, 'Survei selesai', 'Survei Selesai Mitra'])
            ->assertDontSee('Terlambat')
            ->assertDontSee('Tepat waktu');

        $pegawai = User::query()->where('email', 'pegawai@bps.go.id')->firstOrFail();
        $this->actingAs($admin)->get('/admin/mitra/'.$pegawai->id)->assertNotFound();
        $this->actingAs($pegawai)->get('/admin/mitra')->assertForbidden();
    }

    public function test_admin_dashboard_combines_monitoring_with_attention_list(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@bps.go.id')->firstOrFail();
        $pegawai = User::query()->where('email', 'pegawai@bps.go.id')->firstOrFail();
        $draftSurvey = Survey::query()->create([
            'type' => Survey::TYPE_PAPI,
            'title' => 'Survei Draft Dashboard',
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'Draft',
            'created_by' => $admin->id,
            'total_target' => 0,
        ]);
        $overdueSurvey = Survey::query()->create([
            'type' => Survey::TYPE_PAPI,
            'title' => 'Survei Lewat Tenggat Dashboard',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
            'status' => 'Berjalan',
            'created_by' => $admin->id,
            'total_target' => 0,
        ]);
        $capiSurvey = Survey::query()->create([
            'type' => Survey::TYPE_CAPI,
            'title' => 'Survei CAPI Tanpa Data',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'Berjalan',
            'created_by' => $admin->id,
            'total_target' => 0,
        ]);

        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Ringkasan survei')
            ->assertSee('Capaian survei berjalan')
            ->assertSee('Status pendataan')
            ->assertSee('Entri PAPI harian')
            ->assertSee('Capaian per survei')
            ->assertSee('Progres per kecamatan')
            ->assertDontSee('Perlu tindakan')
            ->assertDontSee('Lingkup data');

        // Dashboard selalu merangkum seluruh survei; rincian satu survei dibuka lewat Monitoring.
        $susenas = Survey::query()->where('title', 'SUSENAS')->firstOrFail();
        $this->actingAs($admin)->get('/admin/dashboard?survey_id='.$susenas->id)
            ->assertOk()
            ->assertDontSee('name="survey_id"', false)
            ->assertSee($overdueSurvey->title)
            // Daftar survei di dashboard hanya tampilan, tanpa tautan ke monitoring.
            ->assertDontSee('href="/admin/monitoring/progres?survey='.$susenas->id.'"', false);

        $this->actingAs($pegawai)->get('/pegawai/dashboard')
            ->assertOk()
            ->assertDontSee('Perlu tindakan');
        $this->actingAs($pegawai)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_mitra_target_cannot_drop_below_rutas_already_held(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@bps.go.id')->firstOrFail();
        $survey = Survey::query()->where('title', 'SUSENAS')->firstOrFail();
        $assignment = $survey->assignments()->has('entries')->withCount('entries')->firstOrFail();
        $held = $assignment->entries_count;

        $this->actingAs($admin)->from('/admin/surveys/'.$survey->id.'/assignments')
            ->put('/admin/surveys/'.$survey->id.'/assignments/'.$assignment->id, ['target' => $held - 1])
            ->assertSessionHasErrors('target');
        $this->assertSame((int) $assignment->target, (int) $assignment->fresh()->target);

        $this->actingAs($admin)
            ->put('/admin/surveys/'.$survey->id.'/assignments/'.$assignment->id, ['target' => $held])
            ->assertSessionHasNoErrors();
        $this->assertSame($held, (int) $assignment->fresh()->target);
    }

    public function test_admin_user_search_filters_by_name_or_email(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@bps.go.id')->firstOrFail();
        $mitra = User::query()->where('email', 'mitra@bps.go.id')->firstOrFail();
        $otherMitra = User::query()->role('mitra')->whereKeyNot($mitra->id)->firstOrFail();

        $this->actingAs($admin)->get('/admin/users?role=mitra&q='.urlencode($mitra->name))
            ->assertOk()
            ->assertSee($mitra->email)
            ->assertDontSee($otherMitra->email)
            ->assertViewHas('search', $mitra->name);

        $this->actingAs($admin)->get('/admin/users?role=mitra&q=mitra%40bps.go.id')
            ->assertOk()
            ->assertSee($mitra->email)
            ->assertDontSee($otherMitra->email);

        $this->actingAs($admin)->get('/admin/users?role=all&q=tidak-ada-yang-cocok')
            ->assertOk()
            ->assertSee('Tidak ada pengguna yang cocok');
    }

    public function test_profile_password_form_redirects_back_with_status(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@bps.go.id')->firstOrFail();

        $this->actingAs($admin)->get('/admin/profile')
            ->assertOk()
            ->assertSee('Ubah kata sandi')
            ->assertDontSee('/admin/teams', false);

        $this->actingAs($admin)
            ->from('/admin/profile')
            ->post('/profile/password', [
                'current_password' => 'password123',
                'password' => 'passwordbaru123',
                'password_confirmation' => 'passwordbaru123',
            ])
            ->assertRedirect('/admin/profile')
            ->assertSessionHas('status', 'Kata sandi berhasil diubah.');

        $this->assertTrue(Hash::check('passwordbaru123', $admin->fresh()->password));

        $this->actingAs($admin)
            ->postJson('/profile/password', [
                'current_password' => 'passwordbaru123',
                'password' => 'passwordlagi123',
                'password_confirmation' => 'passwordlagi123',
            ])
            ->assertOk()
            ->assertJson(['message' => 'Password berhasil diubah.']);
    }

    public function test_admin_management_pages_render_for_papi_and_capi_surveys(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@bps.go.id')->firstOrFail();
        $mitra = User::query()->where('email', 'mitra@bps.go.id')->firstOrFail();
        $papi = Survey::query()->where('title', 'SUSENAS')->firstOrFail();
        $capi = Survey::query()->create([
            'type' => Survey::TYPE_CAPI,
            'title' => 'Survei CAPI Halaman',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'Berjalan',
            'created_by' => $admin->id,
            'total_target' => 0,
        ]);

        $this->actingAs($admin)->get('/admin/surveys')->assertOk()->assertSee('SUSENAS')->assertSee('Survei CAPI Halaman');
        $this->actingAs($admin)->get('/admin/surveys/create')->assertOk()->assertSee('Metode pendataan')->assertDontSee('name="team_id"', false);
        $this->actingAs($admin)->get('/admin/surveys/'.$papi->id)->assertOk()->assertSee('Progres mitra')->assertSee('Sisa waktu');
        $this->actingAs($admin)->get('/admin/surveys/'.$capi->id)->assertOk()->assertSee('Riwayat data FASIH')->assertDontSee('Progres mitra');
        $this->actingAs($admin)->get('/admin/surveys/'.$papi->id.'/edit')->assertOk()->assertSee('Informasi survei')->assertSee('Tindakan lanjutan');
        $this->actingAs($admin)->get('/admin/surveys/'.$capi->id.'/edit')->assertOk()->assertSee('Data FASIH')->assertDontSee('Langkah pengaturan survei');
        $this->actingAs($admin)->get('/admin/surveys/'.$papi->id.'/checkpoints')->assertOk()->assertSee('Target bertahap');
        $this->actingAs($admin)->get('/admin/entri-papi?survey='.$papi->id)->assertOk()->assertSee('Data Entri PAPI')->assertSee('SUSENAS');
        $this->actingAs($admin)->get('/admin/entri-papi?survey='.$capi->id)->assertOk()->assertDontSee('Survei CAPI Halaman');
        $this->actingAs($admin)->get('/admin/mitra/'.$mitra->id)->assertOk()->assertSee('SUSENAS');
        $this->actingAs($admin)->get('/admin/logs')->assertOk()->assertSee('Log aktivitas');
        $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->assertDontSee('href="/admin/teams"', false);
    }
}
