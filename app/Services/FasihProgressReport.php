<?php

namespace App\Services;

use App\Models\District;
use App\Models\FasihProgressRow;
use App\Models\SlsArea;
use App\Models\User;
use App\Models\Village;
use Illuminate\Support\Collection;

/**
 * Merekap baris progres FASIH per kecamatan, desa, SLS, atau mitra.
 */
class FasihProgressReport
{
    /**
     * @var array<string, string>
     */
    public const LEVELS = [
        'kecamatan' => 'Kecamatan',
        'desa' => 'Desa/Kelurahan',
        'sls' => 'SLS',
        'mitra' => 'Mitra',
    ];

    /** @var Collection<string, string> */
    private Collection $districtNames;

    /** @var Collection<string, string> */
    private Collection $villageNames;

    /** @var Collection<string, string> */
    private Collection $slsNames;

    /** @var Collection<int, string> */
    private Collection $userNames;

    public function __construct()
    {
        $this->districtNames = District::query()->whereNotNull('code')->pluck('name', 'code');
        $this->villageNames = Village::query()->whereNotNull('code')->pluck('name', 'code');
        $this->slsNames = SlsArea::query()->orderBy('code')->get(['code', 'name'])
            ->mapWithKeys(fn (SlsArea $sls): array => [substr($sls->code, 0, 14) => $sls->name]);
        $this->userNames = collect();
    }

    /**
     * @param  Collection<int, FasihProgressRow>  $rows
     * @return array{total: int, open: int, draft: int, submit: int, other: int, percent: float, mitra_count: int, sls_count: int}
     */
    public function totals(Collection $rows): array
    {
        return [
            ...$this->sumCounts($rows),
            'mitra_count' => $rows->pluck('email')->unique()->count(),
            'sls_count' => $rows->pluck('sls_code')->unique()->count(),
        ];
    }

    /**
     * @param  Collection<int, FasihProgressRow>  $rows
     * @return Collection<int, array{key: string, code: string, name: string, context: string, pencacah?: string|null, mitra: list<string>, mitra_count: int, sls_count: int, total: int, open: int, draft: int, submit: int, other: int, percent: float, drill: array<string, string>}>
     */
    public function groupBy(Collection $rows, string $level): Collection
    {
        $this->loadUserNames($rows);

        return $this->groups($rows, $level);
    }

    /**
     * Rekap bertingkat: setiap kelompok pada tingkat pertama membawa rekap tingkat berikutnya di `children`,
     * mis. kecamatan > desa > SLS.
     *
     * @param  Collection<int, FasihProgressRow>  $rows
     * @param  list<string>  $levels
     * @return Collection<int, array<string, mixed>>
     */
    public function tree(Collection $rows, array $levels): Collection
    {
        $this->loadUserNames($rows);

        return $this->nest($rows, $levels);
    }

    /**
     * @param  Collection<int, FasihProgressRow>  $rows
     * @param  list<string>  $levels
     * @return Collection<int, array<string, mixed>>
     */
    private function nest(Collection $rows, array $levels): Collection
    {
        $level = array_shift($levels);
        $rowsByKey = $rows->groupBy($this->groupColumn($level));

        return $this->groups($rows, $level)->map(fn (array $group): array => [
            ...$group,
            'level' => $level,
            'children' => $levels === [] ? collect() : $this->nest($rowsByKey->get($group['key'], collect())->values(), $levels),
        ]);
    }

    /**
     * @param  Collection<int, FasihProgressRow>  $rows
     */
    private function loadUserNames(Collection $rows): void
    {
        $this->userNames = User::query()->whereIn('id', $rows->pluck('user_id')->filter()->unique())->pluck('name', 'id');
    }

    private function groupColumn(string $level): string
    {
        return match ($level) {
            'kecamatan' => 'district_code',
            'desa' => 'village_code',
            'sls' => 'sls_code',
            default => 'email',
        };
    }

    /**
     * @param  Collection<int, FasihProgressRow>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function groups(Collection $rows, string $level): Collection
    {
        $groups = $rows->groupBy($this->groupColumn($level))->map(function (Collection $groupRows, string $key) use ($level): array {
            $first = $groupRows->first();

            return [
                'key' => $key,
                ...$this->describe($level, $first),
                'mitra' => $groupRows->unique('email')->map(fn (FasihProgressRow $row): string => $this->mitraName($row))->sort()->values()->all(),
                'mitra_count' => $groupRows->pluck('email')->unique()->count(),
                'sls_count' => $groupRows->pluck('sls_code')->unique()->count(),
                ...$this->sumCounts($groupRows),
                'drill' => $this->drillFilters($level, $first),
            ];
        });

        return $level === 'mitra'
            ? $groups->sortBy(fn (array $group): string => strtolower($group['name']))->values()
            : $groups->sortKeys()->values();
    }

    public function districtName(string $districtCode): string
    {
        return $this->districtNames->get($districtCode) ?? 'Tidak terpetakan';
    }

    public function villageName(string $villageCode): string
    {
        return $this->villageNames->get($villageCode) ?? 'Tidak terpetakan';
    }

    public function slsName(string $slsCode): string
    {
        $slsNumber = substr($slsCode, 10, 4);

        return $this->slsNames->get($slsCode)
            ?? (preg_match('/^\d{14}$/', $slsCode) === 1 && $slsNumber !== '0000' ? 'SLS '.$slsNumber : 'Tidak terpetakan');
    }

    /**
     * Label SLS yang dibawa baris (mis. SLS berupa teks bebas pada survei PAPI) melengkapi master SLS.
     *
     * @param  Collection<int, FasihProgressRow>  $rows
     */
    public function registerSlsLabels(Collection $rows): void
    {
        $rows->filter(fn (FasihProgressRow $row): bool => filled($row->sls_label ?? null))
            ->each(fn (FasihProgressRow $row) => $this->slsNames->put($row->sls_code, $row->sls_label));
    }

    public function mitraName(FasihProgressRow $row): string
    {
        return $this->pencacahName($row) ?? $row->username ?? $row->email;
    }

    /**
     * Nama pencacah dari kolom namaPetugas pada file import, atau dari akun SIMPROCA dengan email yang sama.
     */
    public function pencacahName(FasihProgressRow $row): ?string
    {
        return $row->pencacah_name ?? $this->userNames->get($row->user_id);
    }

    /**
     * @param  Collection<int, FasihProgressRow>  $rows
     * @return array{total: int, open: int, draft: int, submit: int, other: int, percent: float}
     */
    private function sumCounts(Collection $rows): array
    {
        $total = (int) $rows->sum('total_region');
        $submit = (int) $rows->sum('submit_count');

        return [
            'total' => $total,
            'open' => (int) $rows->sum('open_count'),
            'draft' => (int) $rows->sum('draft_count'),
            'submit' => $submit,
            'other' => (int) $rows->sum('other_count'),
            'percent' => $total > 0 ? round($submit / $total * 100, 1) : 0.0,
        ];
    }

    /**
     * @return array{code: string, name: string, context: string, pencacah?: string|null}
     */
    private function describe(string $level, FasihProgressRow $row): array
    {
        return match ($level) {
            'kecamatan' => [
                'code' => substr($row->district_code, 4, 3),
                'name' => $this->districtName($row->district_code),
                'context' => '',
            ],
            'desa' => [
                'code' => substr($row->village_code, 4, 6),
                'name' => $this->villageName($row->village_code),
                'context' => $this->districtName($row->district_code),
            ],
            'sls' => [
                'code' => preg_match('/^\d{14}$/', $row->sls_code) === 1 ? substr($row->sls_code, 10, 4) : '—',
                'name' => $this->slsName($row->sls_code),
                'context' => $this->villageName($row->village_code).' · '.$this->districtName($row->district_code),
            ],
            default => [
                'code' => '',
                'name' => $this->mitraName($row),
                'context' => $row->email,
                'pencacah' => $this->pencacahName($row),
            ],
        };
    }

    /**
     * Filter tujuan saat baris diklik (drill-down ke tingkat berikutnya).
     *
     * @return array<string, string>
     */
    private function drillFilters(string $level, FasihProgressRow $row): array
    {
        return match ($level) {
            'kecamatan' => ['kecamatan' => $row->district_code],
            'desa' => ['kecamatan' => $row->district_code, 'desa' => $row->village_code],
            'sls' => ['kecamatan' => $row->district_code, 'desa' => $row->village_code, 'sls' => $row->sls_code],
            default => ['mode' => 'mitra', 'mitra' => $row->email],
        };
    }
}
