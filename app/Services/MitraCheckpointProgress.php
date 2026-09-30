<?php

namespace App\Services;

use App\Models\FasihProgressRow;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyCheckpoint;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Capaian tiap mitra pada satu survei, untuk dibandingkan dengan target checkpoint.
 * PAPI dihitung dari alokasi dan entri terkirim; CAPI dari data FASIH terbaru per email pencacah.
 */
class MitraCheckpointProgress
{
    /**
     * @return Collection<int, array{user: User|null, name: string, progress: int, target: int, percent: float}>
     */
    public function forSurvey(Survey $survey): Collection
    {
        return $survey->isCapi() ? $this->capi($survey) : $this->papi($survey);
    }

    /**
     * Mitra yang capaiannya masih di bawah persentase target checkpoint, dari yang terendah.
     *
     * @return Collection<int, array{user: User|null, name: string, progress: int, target: int, percent: float}>
     */
    public function below(Survey $survey, SurveyCheckpoint $checkpoint): Collection
    {
        return $this->forSurvey($survey)
            ->filter(fn (array $mitra): bool => $mitra['target'] > 0 && $mitra['percent'] < $checkpoint->target_percentage)
            ->sortBy('percent')
            ->values();
    }

    /**
     * @return Collection<int, array{user: User|null, name: string, progress: int, target: int, percent: float}>
     */
    private function papi(Survey $survey): Collection
    {
        return $survey->assignments()->with('mitra')->get()
            ->filter(fn (SurveyAssignment $assignment): bool => $assignment->mitra !== null)
            ->map(fn (SurveyAssignment $assignment): array => $this->row(
                $assignment->mitra,
                $assignment->mitra->name,
                (int) $assignment->current_progress,
                (int) $assignment->target,
            ))
            ->values();
    }

    /**
     * @return Collection<int, array{user: User|null, name: string, progress: int, target: int, percent: float}>
     */
    private function capi(Survey $survey): Collection
    {
        $import = $survey->latestFasihImport;
        if (! $import) {
            return collect();
        }

        return $import->rows()->with('user')->get()
            ->groupBy(fn (FasihProgressRow $row): string => strtolower($row->email))
            ->map(function (Collection $rows): array {
                $first = $rows->first();
                $user = $rows->pluck('user')->filter()->first();

                return $this->row(
                    $user,
                    $first->pencacah_name ?? $user?->name ?? $first->username ?? $first->email,
                    (int) $rows->sum('submit_count'),
                    (int) $rows->sum('total_region'),
                );
            })
            ->values();
    }

    /**
     * @return array{user: User|null, name: string, progress: int, target: int, percent: float}
     */
    private function row(?User $user, string $name, int $progress, int $target): array
    {
        return [
            'user' => $user,
            'name' => $name,
            'progress' => $progress,
            'target' => $target,
            'percent' => $target > 0 ? round(min(100, $progress / $target * 100), 1) : 0.0,
        ];
    }
}
