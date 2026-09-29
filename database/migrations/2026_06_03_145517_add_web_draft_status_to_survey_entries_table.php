<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('survey_entries', function (Blueprint $table): void {
            $table->string('entry_status', 20)->default('submitted')->after('note');
            $table->timestamp('submitted_at')->nullable()->after('entry_status');
            $table->foreignId('district_id')->nullable()->change();
            $table->string('evidence_photo_path')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('survey_entries', function (Blueprint $table): void {
            $table->string('evidence_photo_path')->nullable(false)->change();
            $table->foreignId('district_id')->nullable(false)->change();
            $table->dropColumn(['entry_status', 'submitted_at']);
        });
    }
};
