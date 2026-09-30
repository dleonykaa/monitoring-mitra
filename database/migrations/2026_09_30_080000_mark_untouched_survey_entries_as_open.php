<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Entri draft hasil upload template yang belum disentuh mitra (tanpa isian dan tanpa foto)
     * dipindahkan ke status "open", mengikuti alur Open -> Draft -> Selesai seperti FASIH.
     */
    public function up(): void
    {
        DB::table('survey_entries')
            ->where('entry_status', 'draft')
            ->whereNull('evidence_photo_path')
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('entry_variable_values')
                    ->whereColumn('entry_variable_values.survey_entry_id', 'survey_entries.id')
                    ->whereNotNull('entry_variable_values.value')
                    ->where('entry_variable_values.value', '!=', '');
            })
            ->update(['entry_status' => 'open']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('survey_entries')->where('entry_status', 'open')->update(['entry_status' => 'draft']);
    }
};
