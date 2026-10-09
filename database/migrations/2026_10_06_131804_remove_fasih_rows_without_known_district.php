<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Baris FASIH yang kode wilayahnya bukan kecamatan terdaftar (mis. kode tingkat kabupaten
     * 3101000000000000) dihapus, mengikuti importer yang kini melewati baris seperti itu.
     * Jumlah baris tiap impor dan total target survei CAPI disesuaikan.
     */
    public function up(): void
    {
        $knownDistricts = DB::table('districts')->whereNotNull('code')->pluck('code');

        DB::table('fasih_progress_rows')->whereNotIn('district_code', $knownDistricts)->delete();

        foreach (DB::table('fasih_imports')->pluck('id') as $importId) {
            DB::table('fasih_imports')->where('id', $importId)->update([
                'row_count' => DB::table('fasih_progress_rows')->where('fasih_import_id', $importId)->count(),
            ]);
        }

        $latestImports = DB::table('fasih_imports')->whereNotNull('survey_id')->groupBy('survey_id')->selectRaw('survey_id, max(id) as import_id')->get();
        foreach ($latestImports as $latest) {
            DB::table('surveys')->where('id', $latest->survey_id)->where('type', 'capi')->update([
                'total_target' => (int) DB::table('fasih_progress_rows')->where('fasih_import_id', $latest->import_id)->sum('total_region'),
            ]);
        }
    }

    /**
     * Baris yang dihapus tidak dapat dikembalikan; impor ulang file FASIH bila diperlukan.
     */
    public function down(): void
    {
        //
    }
};
