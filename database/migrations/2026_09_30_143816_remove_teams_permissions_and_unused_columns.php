<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Merapikan skema dari fitur yang sudah tidak ada:
     * - tim (menu tim sudah dihapus; survei baru tidak pernah punya tim),
     * - validasi entri lama dan catatan pegawai,
     * - nama responden dari API mobile,
     * - rumus/aturan validasi survei,
     * - permission Spatie (otorisasi hanya memakai peran).
     */
    public function up(): void
    {
        Schema::table('surveys', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('team_id');
            $table->dropColumn(['validation_formula', 'validation_rules']);
        });

        Schema::table('survey_entries', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('validated_by');
            $table->dropColumn(['is_valid', 'note', 'internal_note', 'respondent_name']);
        });

        Schema::dropIfExists('team_user');
        Schema::dropIfExists('teams');

        DB::table('role_has_permissions')->delete();
        DB::table('model_has_permissions')->delete();
        DB::table('permissions')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Mengembalikan struktur tabel dan kolom (datanya tidak ikut kembali).
     */
    public function down(): void
    {
        Schema::create('teams', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('team_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['team_id', 'user_id']);
        });

        Schema::table('surveys', function (Blueprint $table): void {
            $table->foreignId('team_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->text('validation_formula')->nullable()->after('status');
            $table->json('validation_rules')->nullable()->after('validation_formula');
        });

        Schema::table('survey_entries', function (Blueprint $table): void {
            $table->string('respondent_name')->nullable()->after('no_urut_ruta');
            $table->boolean('is_valid')->nullable()->after('evidence_photo_path');
            $table->foreignId('validated_by')->nullable()->after('is_valid')->constrained('users')->nullOnDelete();
            $table->text('note')->nullable()->after('validated_by');
            $table->text('internal_note')->nullable()->after('note');
        });
    }
};
