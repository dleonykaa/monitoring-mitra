<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('survey_entries', function (Blueprint $table): void {
            $table->boolean('is_valid')->nullable()->after('evidence_photo_path');
            $table->foreignId('validated_by')->nullable()->after('is_valid')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('survey_entries', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('validated_by');
            $table->dropColumn('is_valid');
        });
    }
};
