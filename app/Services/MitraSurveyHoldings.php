<?php

namespace App\Services;

use App\Models\FasihImport;
use App\Models\FasihProgressRow;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Survei yang dipegang mitra: survei PAPI dari alokasi, survei CAPI dari data FASIH terbaru
 * (baris dengan akun atau email yang sama). Hanya berisi fakta beban dan progres, tanpa penilaian.
 */
class MitraSurveyHoldings
{
    /**
     * @param  Collection<int, User>  $mitraUsers
     * @return Collection<int, Collection<int, array{survey: Survey, target: int, progress: int, percent: float, unit: string}>>
     */
    public function forMitra(Collection $mitraUsers): Collection
    {
        $holdings = $mitraUsers->mapWithKeys(fn (User $user): array => [$user->id => collect()]);
        if ($mitraUsers->isEmpty()) {
            return $holdings;
        }

        SurveyAssignment::query()
            ->with('survey')
            ->whereIn('mitra_id', $mitraUsers->pluck('id'))
            ->get()
            ->each(fn (SurveyAssignment $assignment) => $holdings[$assignment->mitra_id]->push(
                $this->holding($assignment->survey, (int) $assignment->target, (int) $assignment->current_progress, 'ruta')
            ));

        $userIdsByEmail = $mitraUsers->mapWithKeys(fn (User $user): array => [strtolower($user->email) => $user->id]);
        $latestImports = FasihImport::query()
            ->with('survey')
            ->whereIn('id', FasihImport::query()->selectRaw('max(id)')->whereNotNull('survey_id')->groupBy('survey_id'))
            ->get()
            ->filter(fn (FasihImport $import): bool => $import->survey?->isCapi() ?? false)
            ->keyBy('id');

        FasihProgressRow::query()
            ->whereIn('fasih_import_id', $latestImports->keys())
            ->where(fn ($query) => $query
                ->whereIn('user_id', $mitraUsers->pluck('id'))
                ->orWhereIn('email', $userIdsByEmail->keys()))
            ->selectRaw('fasih_import_id, user_id, email, sum(total_region) as beban, sum(submit_count) as submitted')
            ->groupBy('fasih_import_id', 'user_id', 'email')
            ->get()
            ->groupBy(fn (FasihProgressRow $row): int => (int) ($row->user_id ?? $userIdsByEmail[strtolower($row->email)]))
            ->each(function (Collection $rows, int $userId) use ($holdings, $latestImports): void {
                $rows->groupBy('fasih_import_id')->each(fn (Collection $importRows, int $importId) => $holdings[$userId]?->push(
                    $this->holding($latestImports[$importId]->survey, (int) $importRows->sum('beban'), (int) $importRows->sum('submitted'), 'dokumen')
                ));
            });

        return $holdings->map(fn (Collection $items): Collection => $items->sortByDesc(fn (array $item) => $item['survey']->end_date)->values());
    }

    /**
     * @return array{survey: Survey, target: int, progress: int, percent: float, unit: string}
     */
    private function holding(Survey $survey, int $target, int $progress, string $unit): array
    {
        return [
            'survey' => $survey,
            'target' => $target,
            'progress' => $progress,
            'percent' => $target > 0 ? min(100, round($progress / $target * 100, 1)) : 0.0,
            'unit' => $unit,
        ];
    }
}
