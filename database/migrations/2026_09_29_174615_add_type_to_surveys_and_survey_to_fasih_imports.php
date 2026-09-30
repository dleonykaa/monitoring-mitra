<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Survei kini dibuat admin dengan tipe PAPI (entri mitra di SIMPROCA) atau CAPI (progres dari FASIH),
     * tim penanggung jawab bersifat opsional, dan setiap import FASIH terikat ke satu survei CAPI.
     */
    public function up(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->string('type', 10)->default('papi')->after('title');
            $table->foreignId('team_id')->nullable()->change();
        });

        Schema::table('fasih_imports', function (Blueprint $table) {
            $table->foreignId('survey_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        $this->attachOrphanImportsToExampleSurvey();
    }

    public function down(): void
    {
        Schema::table('fasih_imports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('survey_id');
        });

        Schema::table('surveys', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    /**
     * Data import FASIH yang sudah ada sebelum fitur ini dipindahkan ke satu survei CAPI contoh.
     */
    private function attachOrphanImportsToExampleSurvey(): void
    {
        $orphanImports = DB::table('fasih_imports')->whereNull('survey_id')->orderBy('id')->get();
        if ($orphanImports->isEmpty()) {
            return;
        }

        $creatorId = DB::table('users')
            ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', 'admin')
            ->value('users.id') ?? DB::table('users')->value('id');

        if ($creatorId === null) {
            return;
        }

        $latestImport = $orphanImports->last();
        $firstImportDate = Carbon::parse($orphanImports->first()->created_at);

        $surveyId = DB::table('surveys')->insertGetId([
            'team_id' => null,
            'created_by' => $creatorId,
            'title' => 'Survei CAPI FASIH (contoh)',
            'type' => 'capi',
            'description' => 'Survei contoh yang menampung data import FASIH sebelum survei CAPI dikelola per survei.',
            'total_target' => (int) DB::table('fasih_progress_rows')->where('fasih_import_id', $latestImport->id)->sum('total_region'),
            'start_date' => $firstImportDate->toDateString(),
            'end_date' => $firstImportDate->copy()->addMonth()->toDateString(),
            'status' => 'Berjalan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('fasih_imports')->whereNull('survey_id')->update(['survey_id' => $surveyId]);
    }
};
