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
        Schema::table('survey_entries', function (Blueprint $table) {
            $table->string('kode_nks')->nullable()->after('village_id');
            $table->string('sls')->nullable()->after('kode_nks');
            $table->string('ppl')->nullable()->after('sls');
            $table->string('no_urut_ruta')->nullable()->after('ppl');
            $table->string('respondent_name')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('survey_entries', function (Blueprint $table) {
            $table->dropColumn(['kode_nks', 'sls', 'ppl', 'no_urut_ruta']);
            $table->string('respondent_name')->nullable(false)->change();
        });
    }
};
