<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('entry_variable_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('survey_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('survey_variable_id')->constrained()->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entry_variable_values');
    }
};
