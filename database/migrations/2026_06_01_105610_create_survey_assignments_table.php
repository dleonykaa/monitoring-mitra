<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('survey_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mitra_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('target');
            $table->unsignedInteger('current_progress')->default(0);
            $table->timestamps();
            $table->unique(['survey_id', 'mitra_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_assignments');
    }
};
