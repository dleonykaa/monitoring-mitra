<?php

namespace App\Services;

use App\Models\District;
use App\Models\FasihProgressRow;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Ringkasan dashboard admin dan pegawai untuk seluruh survei sekaligus. Rincian per survei,
 * wilayah, dan pencacah tetap berada di halaman Monitoring Progres.
 *
 * Angka progres survei berjalan dihitung dari baris yang sama dengan Monitoring: snapshot FASIH
 * terbaru untuk CAPI dan alokasi + entri mitra untuk PAPI.
 */
class SurveyDashboardOverview
{
    /**
     * Zona waktu tampilan untuk pengelompokan entri harian.
     */
    private const TIMEZONE = 'Asia/Jakarta';

    public function __construct(
        private readonly SurveyProgressSummary $progressSummary,
        private readonly PapiProgressRows $papiRows,
        private readonly FasihProgressReport $report,
    ) {}

    /**
     * @return array{
     *     surveys: Collection<int, Survey>,
     *     surveyStatusCounts: array<string, int>,
     *     surveyTypeCounts: array<string, int>,
     *     runningTotals: array{total: int, open: int, draft: int, submit: int, other: int, percent: float, mitra_count: int, sls_count: int},
     *     districtSummary: Collection<int, array<string, mixed>>,
     *     dailyEntries: Collection<int, array{date: Carbon, count: int}>,
     *     lateAssignments: int,
     *     staleAssignments: Collection<int, SurveyAssignment>,
     * }
     */
    public function build(): array
    {
        $surveys = $this->progressSummary->attach(
            Survey::query()->with('latestFasihImport')->latest()->get()
        );
        $running = $surveys->where('status', 'Berjalan')->values();
        $knownDistricts = District::query()->whereNotNull('code')->pluck('code')->flip();

        $rowsBySurvey = $running->mapWithKeys(fn (Survey $survey): array => [$survey->id => $this->progressRows($survey, $knownDistricts)]);
        $this->attachBreakdown($surveys, $rowsBySurvey);

        $allRows = $rowsBySurvey->flatten(1);
        $mappedRows = $allRows->filter(fn (FasihProgressRow $row): bool => $knownDistricts->has($row->district_code))->values();

        $today = now(self::TIMEZONE)->startOfDay();

        return [
            'surveys' => $surveys,
            'surveyStatusCounts' => collect(['Berjalan', 'Draft', 'Selesai'])
                ->mapWithKeys(fn (string $status): array => [$status => $surveys->where('status', $status)->count()])
                ->all(),
            'surveyTypeCounts' => collect(Survey::TYPES)
                ->mapWithKeys(fn (string $label, string $type): array => [$label => $surveys->where('type', $type)->count()])
                ->all(),
            'runningTotals' => $this->report->totals($allRows),
            'districtSummary' => $this->districtSummary($mappedRows),
            'dailyEntries' => $this->dailyEntries($today),
            'lateAssignments' => SurveyAssignment::query()
                ->whereColumn('current_progress', '<', 'target')
                ->whereHas('survey', fn ($query) => $query->where('status', 'Berjalan')->whereDate('end_date', '<', $today->toDateString()))
                ->count(),
            'staleAssignments' => $this->staleAssignments(),
        ];
    }

    /**
     * Rekap per kecamatan gabungan seluruh survei berjalan; setiap kecamatan membawa rekap
     * desa/kelurahannya pada kunci `villages`.
     *
     * @param  Collection<int, FasihProgressRow>  $mappedRows
     * @return Collection<int, array<string, mixed>>
     */
    private function districtSummary(Collection $mappedRows): Collection
    {
        $villagesByDistrict = $this->report->groupBy($mappedRows, 'desa')
            ->groupBy(fn (array $village): string => $village['drill']['kecamatan']);

        return $this->report->groupBy($mappedRows, 'kecamatan')
            ->map(fn (array $district): array => [
                ...$district,
                'villages' => $villagesByDistrict->get($district['key'], collect())->values(),
            ]);
    }

    /**
     * Baris progres satu survei berjalan, dengan aturan yang sama seperti di Monitoring:
     * baris CAPI di luar master wilayah dibuang, sisa target PAPI tanpa wilayah tetap dihitung.
     *
     * @param  Collection<string, int>  $knownDistricts
     * @return Collection<int, FasihProgressRow>
     */
    private function progressRows(Survey $survey, Collection $knownDistricts): Collection
    {
        if (! $survey->isCapi()) {
            return $this->papiRows->forSurvey($survey);
        }

        return $survey->latestFasihImport
            ? $survey->latestFasihImport->rows()->get()->filter(fn (FasihProgressRow $row): bool => $knownDistricts->has($row->district_code))->values()
            : collect();
    }

    /**
     * Menempelkan rincian submit/draft/open ke setiap survei. Survei yang tidak berjalan
     * hanya punya angka capaian dari SurveyProgressSummary.
     *
     * @param  Collection<int, Survey>  $surveys
     * @param  Collection<int, Collection<int, FasihProgressRow>>  $rowsBySurvey
     */
    private function attachBreakdown(Collection $surveys, Collection $rowsBySurvey): void
    {
        $surveys->each(function (Survey $survey) use ($rowsBySurvey): void {
            $rows = $rowsBySurvey->get($survey->id);
            if ($rows === null) {
                $survey->breakdown = null;

                return;
            }

            $totals = $this->report->totals($rows);
            $survey->breakdown = $totals;
            $survey->progress_target = $totals['total'];
            $survey->progress_count = $totals['submit'];
            $survey->progress_percent = min(100, $totals['percent']);
        });
    }

    /**
     * Jumlah entri PAPI yang diselesaikan mitra per hari pada minggu berjalan, Senin sampai Minggu.
     * Hari yang belum tiba tetap ditampilkan dengan jumlah 0.
     *
     * @return Collection<int, array{date: Carbon, count: int}>
     */
    private function dailyEntries(Carbon $today): Collection
    {
        $start = $today->copy()->startOfWeek(Carbon::MONDAY);

        $counts = SurveyEntry::query()
            ->where('entry_status', SurveyEntry::STATUS_SUBMITTED)
            ->where('submitted_at', '>=', $start->copy()->timezone(config('app.timezone')))
            ->pluck('submitted_at')
            ->countBy(fn (Carbon $submittedAt): string => $submittedAt->copy()->timezone(self::TIMEZONE)->toDateString());

        return collect(range(0, 6))->map(function (int $offset) use ($start, $counts): array {
            $date = $start->copy()->addDays($offset);

            return ['date' => $date, 'count' => (int) $counts->get($date->toDateString(), 0)];
        });
    }

    /**
     * Alokasi survei berjalan yang belum tuntas dan tidak menerima entri selesai dalam 3 hari terakhir.
     *
     * @return Collection<int, SurveyAssignment>
     */
    private function staleAssignments(): Collection
    {
        return SurveyAssignment::query()
            ->with(['survey', 'mitra'])
            ->withMax(['entries as last_entry_at' => fn ($query) => $query->where('entry_status', SurveyEntry::STATUS_SUBMITTED)], 'submitted_at')
            ->whereColumn('current_progress', '<', 'target')
            ->whereHas('survey', fn ($query) => $query->where('status', 'Berjalan'))
            ->get()
            ->filter(fn (SurveyAssignment $assignment): bool => $assignment->last_entry_at === null || Carbon::parse($assignment->last_entry_at)->lte(now()->subDays(3)))
            ->sortBy(fn (SurveyAssignment $assignment): string => (string) $assignment->last_entry_at)
            ->values();
    }
}
