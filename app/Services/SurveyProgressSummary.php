<?php

namespace App\Services;

use App\Models\FasihImport;
use App\Models\FasihProgressRow;
use App\Models\Survey;
use App\Models\SurveyEntry;
use Illuminate\Support\Collection;

/**
 * Menempelkan capaian ringkas (progress_count, progress_target, progress_percent) ke daftar survei.
 * PAPI dihitung dari entri mitra yang sudah dikirim; CAPI dari import FASIH terbaru.
 */
class SurveyProgressSummary
{
    /**
     * @param  Collection<int, Survey>  $surveys
     * @return Collection<int, Survey>
     */
    public function attach(Collection $surveys): Collection
    {
        $surveyIds = $surveys->pluck('id');

        $submittedEntries = SurveyEntry::query()
            ->whereIn('survey_id', $surveyIds)
            ->where('entry_status', 'submitted')
            ->selectRaw('survey_id, count(*) as total')
            ->groupBy('survey_id')
            ->pluck('total', 'survey_id');

        $latestImportIds = FasihImport::query()
            ->whereIn('survey_id', $surveyIds)
            ->selectRaw('survey_id, max(id) as latest_id')
            ->groupBy('survey_id')
            ->pluck('latest_id', 'survey_id');

        $capiTotals = FasihProgressRow::query()
            ->whereIn('fasih_import_id', $latestImportIds->values())
            ->selectRaw('fasih_import_id, sum(total_region) as beban, sum(submit_count) as submitted')
            ->groupBy('fasih_import_id')
            ->get()
            ->keyBy('fasih_import_id');

        return $surveys->each(function (Survey $survey) use ($submittedEntries, $latestImportIds, $capiTotals): void {
            if ($survey->isCapi()) {
                $totals = $capiTotals->get($latestImportIds->get($survey->id));
                $survey->progress_target = (int) ($totals->beban ?? 0);
                $survey->progress_count = (int) ($totals->submitted ?? 0);
            } else {
                $survey->progress_target = (int) $survey->total_target;
                $survey->progress_count = (int) $submittedEntries->get($survey->id, 0);
            }

            $survey->progress_percent = $survey->progress_target > 0
                ? min(100, round($survey->progress_count / $survey->progress_target * 100, 1))
                : 0;
        });
    }
}
