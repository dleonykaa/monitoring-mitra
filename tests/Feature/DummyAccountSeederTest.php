<?php

namespace Tests\Feature;

use App\Models\FasihImport;
use App\Models\FasihProgressRow;
use App\Models\User;
use Database\Seeders\DummyAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DummyAccountSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_fasih_mitra_and_pegawai_accounts_that_can_log_in(): void
    {
        $this->seed();
        $import = FasihImport::query()->create(['file_name' => 'fasih.csv', 'row_count' => 1]);
        $row = FasihProgressRow::query()->create([
            'fasih_import_id' => $import->id,
            'email' => 'sjuhroh12@gmail.com',
            'region_code' => '3101020001000600',
            'district_code' => '3101020',
            'village_code' => '3101020001',
            'sls_code' => '31010200010006',
            'total_region' => 10,
            'open_count' => 10,
            'draft_count' => 0,
            'submit_count' => 0,
            'other_count' => 0,
            'status_breakdown' => ['OPEN' => 10],
        ]);

        $this->seed(DummyAccountSeeder::class);
        $this->seed(DummyAccountSeeder::class);

        $this->assertSame(count(DummyAccountSeeder::MITRA), User::query()->role('mitra')->whereIn('email', array_column(DummyAccountSeeder::MITRA, 'email'))->count());
        $this->assertSame(count(DummyAccountSeeder::PEGAWAI), User::query()->role('pegawai_bps')->whereIn('email', array_column(DummyAccountSeeder::PEGAWAI, 'email'))->count());

        $siti = User::query()->where('email', 'sjuhroh12@gmail.com')->sole();
        $this->assertSame('Siti Juhroh', $siti->name);
        $this->assertMatchesRegularExpression('/^08\d{10}$/', $siti->phone);
        $this->assertTrue(Hash::check('password123', $siti->password));
        $this->assertSame($siti->id, $row->fresh()->user_id);

        $marwan = User::query()->where('email', 'marwan@bps.go.id')->sole();
        $this->assertSame('Marwan', $marwan->name);
        $this->assertTrue($marwan->hasRole('pegawai_bps'));

        $this->assertSame(
            ['mitra@bps.go.id'],
            User::query()->role('mitra')->where('email', 'not like', '%@gmail.com')->pluck('email')->all(),
            'Semua email mitra berakhiran @gmail.com kecuali akun demo.'
        );

        foreach (['admin@bps.go.id' => '/admin/dashboard', 'pegawai@bps.go.id' => '/pegawai/dashboard', 'mitra@bps.go.id' => '/mitra/dashboard', 'marwan@bps.go.id' => '/pegawai/dashboard'] as $email => $home) {
            $this->post('/login', ['email' => $email, 'password' => 'password123'])->assertRedirect($home);
            $this->post('/logout');
        }

        $this->post('/login', ['email' => 'sjuhroh12@gmail.com', 'password' => 'password123'])->assertRedirect('/mitra/dashboard');
        $this->post('/logout');
        $this->post('/login', ['email' => DummyAccountSeeder::PEGAWAI[0]['email'], 'password' => 'password123'])->assertRedirect('/pegawai/dashboard');
    }
}
