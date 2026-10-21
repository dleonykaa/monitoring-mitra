<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pegawai BPS yang ditunjuk admin sebagai penanggung jawab (PML) sebuah survei.
     */
    public function up(): void
    {
        Schema::create('survey_pegawai', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['survey_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_pegawai');
    }
};
