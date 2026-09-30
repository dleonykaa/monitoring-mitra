<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penanda bahwa peringatan checkpoint sudah dikirim ke mitra, agar tidak terkirim dua kali.
     */
    public function up(): void
    {
        Schema::table('survey_checkpoints', function (Blueprint $table) {
            $table->timestamp('notified_at')->nullable()->after('target_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('survey_checkpoints', function (Blueprint $table) {
            $table->dropColumn('notified_at');
        });
    }
};
