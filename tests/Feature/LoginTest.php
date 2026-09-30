<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_role_lands_on_its_own_dashboard(): void
    {
        $this->seed(RolePermissionSeeder::class);

        foreach (['admin' => '/admin/dashboard', 'pegawai_bps' => '/pegawai/dashboard', 'mitra' => '/mitra/dashboard'] as $role => $home) {
            $user = User::factory()->create(['password' => 'rahasia123']);
            $user->assignRole($role);

            $this->post('/login', ['email' => $user->email, 'password' => 'rahasia123'])->assertRedirect($home);
            $this->get('/')->assertRedirect($home);
            $this->post('/logout');
        }
    }

    public function test_account_without_role_or_inactive_is_rejected(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $noRole = User::factory()->create(['password' => 'rahasia123']);
        $inactive = User::factory()->create(['password' => 'rahasia123', 'is_active' => false]);
        $inactive->assignRole('mitra');

        $this->from('/login')->post('/login', ['email' => $noRole->email, 'password' => 'rahasia123'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Akun Anda belum memiliki peran. Hubungi admin untuk mengaktifkannya.']);
        $this->assertGuest();

        $this->from('/login')->post('/login', ['email' => $inactive->email, 'password' => 'rahasia123'])
            ->assertSessionHasErrors(['email' => 'Akun Anda nonaktif.']);
        $this->assertGuest();

        // Sesi akun tanpa peran (mis. perannya dicabut) diakhiri saat membuka beranda.
        $this->actingAs($noRole)->get('/')->assertRedirect('/login');
        $this->assertGuest();
        $this->get('/ui')->assertRedirect('/');
    }

    public function test_session_ends_as_soon_as_account_is_deactivated(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $mitra = User::factory()->create(['is_active' => true]);
        $mitra->assignRole('mitra');

        $this->actingAs($mitra)->get('/mitra/profile')->assertOk();

        $mitra->update(['is_active' => false]);

        $this->get('/mitra/profile')
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Akun Anda nonaktif.']);
        $this->assertGuest();
    }

    public function test_login_attempts_are_rate_limited(): void
    {
        $user = User::factory()->create(['password' => 'rahasia123']);

        foreach (range(1, 10) as $attempt) {
            $this->post('/login', ['email' => $user->email, 'password' => 'salah-'.$attempt])->assertSessionHasErrors('email');
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'salah-lagi'])->assertStatus(429);
    }
}
