<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Team;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
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

        foreach (['/admin/dashboard', '/admin/users', '/admin/roles', '/admin/teams', '/admin/regions', '/admin/logs', '/admin/monitoring/surveys', '/admin/monitoring/wilayah', '/admin/monitoring/kinerja'] as $route) {
            $this->actingAs($admin)->get($route)->assertOk();
        }

        $this->actingAs($admin)->get('/admin/users?role=pegawai_bps')
            ->assertOk()
            ->assertSee($pegawai->email)
            ->assertDontSee($mitra->email);

        $this->actingAs($admin)->get('/admin/users?role=mitra')
            ->assertOk()
            ->assertSee($mitra->email)
            ->assertDontSee($pegawai->email);

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Admin Test Mitra',
            'email' => 'admin.test.mitra@example.com',
            'password' => 'password123',
            'role' => 'mitra',
        ])->assertRedirect();

        $user = User::query()->where('email', 'admin.test.mitra@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('mitra'));

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

        $role = Role::query()->where('name', 'pegawai_bps')->where('guard_name', 'web')->firstOrFail();
        $this->actingAs($admin)->put('/admin/roles/'.$role->id, [
            'permissions' => ['manage-surveys', 'view-pegawai-dashboard'],
        ])->assertRedirect();

        $this->actingAs($admin)->post('/admin/teams', [
            'name' => 'Tim Admin Test',
            'description' => 'Uji alokasi',
        ])->assertRedirect();
        $team = Team::query()->where('name', 'Tim Admin Test')->firstOrFail();

        $this->actingAs($admin)->post('/admin/teams/'.$team->id.'/assign', [
            'user_id' => $pegawai->id,
        ])->assertRedirect();
        $this->assertTrue($team->users()->whereKey($pegawai->id)->exists());

        $this->actingAs($admin)->delete('/admin/teams/'.$team->id.'/users/'.$pegawai->id)->assertRedirect();
        $this->assertFalse($team->users()->whereKey($pegawai->id)->exists());

        $this->actingAs($admin)->post('/admin/regions/districts', [
            'name' => 'Kecamatan Admin Test',
        ])->assertRedirect();
        $district = District::query()->where('name', 'Kecamatan Admin Test')->firstOrFail();

        $this->actingAs($admin)->put('/admin/regions/districts/'.$district->id, [
            'name' => 'Kecamatan Admin Test Edit',
        ])->assertRedirect();
        $district->refresh();
        $this->assertSame('Kecamatan Admin Test Edit', $district->name);

        $this->actingAs($admin)->post('/admin/regions/villages', [
            'district_id' => $district->id,
            'name' => 'Pulau Admin Test',
            'type' => 'pulau',
        ])->assertRedirect();
        $village = Village::query()->where('name', 'Pulau Admin Test')->firstOrFail();

        $this->actingAs($admin)->put('/admin/regions/villages/'.$village->id, [
            'district_id' => $district->id,
            'name' => 'Pulau Admin Test Edit',
            'type' => 'pulau',
        ])->assertRedirect();
        $village->refresh();
        $this->assertSame('Pulau Admin Test Edit', $village->name);

        $this->actingAs($admin)->delete('/admin/regions/villages/'.$village->id)->assertRedirect();
        $this->assertDatabaseMissing('villages', ['id' => $village->id]);

        $this->actingAs($admin)->get('/admin/logs?action=admin')->assertOk();
        $this->actingAs($admin)->delete('/admin/users/'.$user->id)->assertRedirect();
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }
}
