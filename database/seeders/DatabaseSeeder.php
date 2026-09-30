<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    private const FIRST_NAMES = [
        'Ahmad', 'Budi', 'Siti', 'Dewi', 'Rizky', 'Putri', 'Eko', 'Lestari', 'Hendra', 'Maya',
        'Agus', 'Rina', 'Joko', 'Nur', 'Bambang', 'Sri', 'Andi', 'Tuti', 'Doni', 'Ratna',
        'Fajar', 'Indah', 'Dian', 'Yusuf', 'Fitri', 'Wahyu', 'Ika', 'Rudi', 'Lina', 'Arief',
        'Novi', 'Hadi', 'Yuli', 'Irfan', 'Wulan', 'Bayu', 'Tri', 'Anita', 'Iwan', 'Retno',
        'Dedi', 'Nining', 'Gita', 'Slamet', 'Wati', 'Herman', 'Anggi', 'Rahmat', 'Melati', 'Arya',
    ];

    private const LAST_NAMES = [
        'Santoso', 'Wijaya', 'Lestari', 'Hartono', 'Susilo', 'Marlina', 'Gunawan', 'Kurnia', 'Iswanto', 'Wahyuni',
        'Prasetyo', 'Saputra', 'Handayani', 'Firmansyah', 'Nugroho', 'Permata', 'Ramadhan', 'Anggraini', 'Maharani', 'Kurniawan',
        'Utami', 'Setiawan', 'Pratama', 'Puspita', 'Wijayanti', 'Purnama', 'Cahyono', 'Rahayu', 'Wibowo', 'Kusuma',
        'Hidayat', 'Yulianti', 'Suryadi', 'Damayanti', 'Fauzi', 'Salsabila', 'Ananda', 'Maulana', 'Safitri', 'Nugraha',
    ];

    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            RegionSeeder::class,
            SlsAreaSeeder::class,
        ]);

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@bps.go.id'],
            ['name' => 'Admin BPS', 'phone' => '081200000001', 'password' => 'password123', 'is_active' => true]
        );
        $admin->assignRole('admin');

        $pegawai = User::query()->updateOrCreate(
            ['email' => 'pegawai@bps.go.id'],
            ['name' => 'Pegawai BPS', 'phone' => '081200000002', 'password' => 'password123', 'is_active' => true]
        );
        $pegawai->assignRole('pegawai_bps');

        // Akun mitra utama untuk login uji coba
        $mitra = User::query()->updateOrCreate(
            ['email' => 'mitra@bps.go.id'],
            ['name' => 'Ahmad Fauzi', 'phone' => '081300000001', 'password' => 'password123', 'is_active' => true]
        );
        $mitra->assignRole('mitra');

        // 9 mitra bernama (baseline kecil, selalu dibuat termasuk saat testing).
        foreach ([
            ['name' => 'Siti Nurhaliza', 'email' => 'siti.nurhaliza@gmail.com', 'phone' => '081300000002'],
            ['name' => 'Budi Santoso', 'email' => 'budi.santoso@gmail.com', 'phone' => '081300000003'],
            ['name' => 'Dewi Anggraini', 'email' => 'dewi.anggraini@gmail.com', 'phone' => '081300000004'],
            ['name' => 'Rizky Ramadhan', 'email' => 'rizky.ramadhan@gmail.com', 'phone' => '081300000005'],
            ['name' => 'Putri Maharani', 'email' => 'putri.maharani@gmail.com', 'phone' => '081300000006'],
            ['name' => 'Eko Prasetyo', 'email' => 'eko.prasetyo@gmail.com', 'phone' => '081300000007'],
            ['name' => 'Lestari Wulandari', 'email' => 'lestari.wulandari@gmail.com', 'phone' => '081300000008'],
            ['name' => 'Hendra Kurniawan', 'email' => 'hendra.kurniawan@gmail.com', 'phone' => '081300000009'],
            ['name' => 'Maya Sari', 'email' => 'maya.sari@gmail.com', 'phone' => '081300000010'],
        ] as $mitraData) {
            $namedMitra = User::query()->updateOrCreate(
                ['email' => $mitraData['email']],
                ['name' => $mitraData['name'], 'phone' => $mitraData['phone'], 'password' => 'password123', 'is_active' => true]
            );
            $namedMitra->assignRole('mitra');
        }

        // Skala penuh (20 pegawai, 50 mitra, data survei masif) hanya dibuat di luar
        // environment testing agar suite tes tetap cepat.
        if (! app()->environment('testing')) {
            for ($i = 0; $i < 19; $i++) {
                $name = $this->generateName($i, 'Pegawai');
                $email = 'pegawai'.($i + 2).'@bps.go.id';
                $extraPegawai = User::query()->updateOrCreate(
                    ['email' => $email],
                    ['name' => $name, 'phone' => '0812'.str_pad((string) ($i + 10), 8, '0', STR_PAD_LEFT), 'password' => 'password123', 'is_active' => true]
                );
                $extraPegawai->assignRole('pegawai_bps');
            }

            for ($i = 0; $i < 40; $i++) {
                $name = $this->generateName($i, 'Mitra');
                $email = 'mitra'.($i + 11).'@gmail.com';
                $extraMitra = User::query()->updateOrCreate(
                    ['email' => $email],
                    ['name' => $name, 'phone' => '0813'.str_pad((string) ($i + 20), 8, '0', STR_PAD_LEFT), 'password' => 'password123', 'is_active' => true]
                );
                $extraMitra->assignRole('mitra');
            }
        }

        $this->call(DemoDataSeeder::class);

        // Akun pencacah FASIH dan pegawai uji coba; tidak dibuat saat testing agar data tes tetap kecil.
        if (! app()->environment('testing')) {
            $this->call(DummyAccountSeeder::class);
        }
    }

    private function generateName(int $index, string $salt): string
    {
        $firstCount = count(self::FIRST_NAMES);
        $lastCount = count(self::LAST_NAMES);
        $offset = $salt === 'Pegawai' ? 3 : 0;

        $first = self::FIRST_NAMES[($index + $offset) % $firstCount];
        $last = self::LAST_NAMES[(($index + $offset) * 7 + 3) % $lastCount];

        return $first.' '.$last;
    }
}
