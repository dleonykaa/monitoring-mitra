<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fasih_progress_rows', function (Blueprint $table) {
            $table->string('pencacah_name')->nullable()->after('username');
        });
    }

    public function down(): void
    {
        Schema::table('fasih_progress_rows', function (Blueprint $table) {
            $table->dropColumn('pencacah_name');
        });
    }
};
