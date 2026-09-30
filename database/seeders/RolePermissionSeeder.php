<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Tiga peran SIMPROCA. Otorisasi hanya memakai peran (middleware role:), tanpa permission terpisah.
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['admin', 'pegawai_bps', 'mitra'] as $role) {
            Role::query()->firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }
}
