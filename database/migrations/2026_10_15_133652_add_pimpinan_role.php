<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Peran pimpinan: memantau seluruh survei tanpa bisa mengubah data.
     */
    public function up(): void
    {
        if (DB::table('roles')->where('name', 'pimpinan')->where('guard_name', 'web')->doesntExist()) {
            DB::table('roles')->insert(['name' => 'pimpinan', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Akun pimpinan kehilangan perannya; akunnya sendiri tetap ada.
     */
    public function down(): void
    {
        DB::table('roles')->where('name', 'pimpinan')->where('guard_name', 'web')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
