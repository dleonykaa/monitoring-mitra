<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->date('checkpoint_date');
            $table->unsignedTinyInteger('target_percentage');
            $table->timestamps();
            $table->index(['survey_id', 'checkpoint_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_checkpoints');
    }
};
