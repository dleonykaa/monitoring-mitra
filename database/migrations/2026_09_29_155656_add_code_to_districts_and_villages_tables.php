<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kode wilayah BPS: kecamatan 7 digit (3101010), desa/kelurahan 10 digit (3101010001).
     */
    public function up(): void
    {
        Schema::table('districts', function (Blueprint $table) {
            $table->string('code', 7)->nullable()->unique()->after('id');
        });

        Schema::table('villages', function (Blueprint $table) {
            $table->string('code', 10)->nullable()->unique()->after('district_id');
        });
    }

    public function down(): void
    {
        Schema::table('villages', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });

        Schema::table('districts', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};
