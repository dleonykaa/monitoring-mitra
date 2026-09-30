<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fasih_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('file_name');
            $table->unsignedInteger('row_count')->default(0);
            $table->timestamps();
        });

        Schema::create('fasih_progress_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fasih_import_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('fasih_user_id')->nullable();
            $table->string('username')->nullable();
            $table->string('email');
            $table->string('region_code', 16);
            $table->string('district_code', 7);
            $table->string('village_code', 10);
            $table->string('sls_code', 14);
            $table->unsignedInteger('total_region')->default(0);
            $table->unsignedInteger('open_count')->default(0);
            $table->unsignedInteger('draft_count')->default(0);
            $table->unsignedInteger('submit_count')->default(0);
            $table->unsignedInteger('other_count')->default(0);
            $table->json('status_breakdown')->nullable();
            $table->timestamps();
            $table->index(['fasih_import_id', 'region_code']);
            $table->index(['fasih_import_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fasih_progress_rows');
        Schema::dropIfExists('fasih_imports');
    }
};
