<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'manage-users',
            'manage-regions',
            'manage-teams',
            'manage-surveys',
            'view-pegawai-dashboard',
            'view-admin-monitoring',
            'submit-survey-entry',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'sanctum']);
        }

        $admin = Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $pegawai = Role::query()->firstOrCreate(['name' => 'pegawai_bps', 'guard_name' => 'web']);
        $mitraWeb = Role::query()->firstOrCreate(['name' => 'mitra', 'guard_name' => 'web']);
        $mitraApi = Role::query()->firstOrCreate(['name' => 'mitra', 'guard_name' => 'sanctum']);

        $admin->syncPermissions(['manage-users', 'manage-regions', 'manage-teams', 'manage-surveys', 'view-admin-monitoring']);
        $pegawai->syncPermissions(['manage-surveys', 'view-pegawai-dashboard']);
        $mitraWeb->syncPermissions(['submit-survey-entry']);
        $mitraApi->syncPermissions(['submit-survey-entry']);
    }
}
