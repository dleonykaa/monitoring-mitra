<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Aplikasi Android mitra tidak jadi dibuat, sehingga API mobile beserta token Sanctum,
     * token perangkat, dan role/permission guard "sanctum" tidak dipakai lagi.
     */
    public function up(): void
    {
        Schema::dropIfExists('user_device_tokens');
        Schema::dropIfExists('personal_access_tokens');

        DB::table('roles')->where('guard_name', 'sanctum')->delete();
        DB::table('permissions')->where('guard_name', 'sanctum')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Mengembalikan struktur tabel; role/permission guard "sanctum" tidak dibuat ulang.
     */
    public function down(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('user_device_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_token')->unique();
            $table->string('platform')->default('android');
            $table->timestamps();
        });
    }
};
