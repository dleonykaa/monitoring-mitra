<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Mitra hanya mengerjakan ruta hasil alokasi, jadi target tiap alokasi disamakan dengan jumlah
     * rutanya (sisa target tanpa wilayah dihapus) dan total target survei PAPI dihitung ulang.
     */
    public function up(): void
    {
        $rutaCounts = DB::table('survey_entries')
            ->whereNotNull('survey_assignment_id')
            ->groupBy('survey_assignment_id')
            ->selectRaw('survey_assignment_id, count(*) as ruta')
            ->pluck('ruta', 'survey_assignment_id');

        foreach (DB::table('survey_assignments')->get(['id', 'target']) as $assignment) {
            $ruta = (int) ($rutaCounts[$assignment->id] ?? 0);
            if ((int) $assignment->target !== $ruta) {
                DB::table('survey_assignments')->where('id', $assignment->id)->update(['target' => $ruta]);
            }
        }

        foreach (DB::table('surveys')->where('type', '!=', 'capi')->pluck('id') as $surveyId) {
            DB::table('surveys')->where('id', $surveyId)->update([
                'total_target' => (int) DB::table('survey_assignments')->where('survey_id', $surveyId)->sum('target'),
            ]);
        }
    }

    /**
     * Target lama tidak disimpan, sehingga tidak dapat dikembalikan.
     */
    public function down(): void
    {
        //
    }
};
