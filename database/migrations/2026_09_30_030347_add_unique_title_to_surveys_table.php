<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nama survei/sensus harus unik. Judul kembar yang sudah ada diberi akhiran nomor
     * agar indeks unik bisa dibuat tanpa menghapus data.
     */
    public function up(): void
    {
        DB::table('surveys')->orderBy('id')->get(['id', 'title'])
            ->groupBy(fn ($survey) => mb_strtolower(trim($survey->title)))
            ->filter(fn ($group) => $group->count() > 1)
            ->each(fn ($group) => $group->skip(1)->each(fn ($survey) => DB::table('surveys')
                ->where('id', $survey->id)
                ->update(['title' => trim($survey->title).' (#'.$survey->id.')'])));

        Schema::table('surveys', function (Blueprint $table) {
            $table->unique('title');
        });
    }

    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->dropUnique(['title']);
        });
    }
};
