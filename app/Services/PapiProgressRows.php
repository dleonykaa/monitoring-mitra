<?php

namespace App\Services;

use App\Models\FasihProgressRow;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyEntry;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Menyusun progres survei PAPI dalam bentuk baris yang sama dengan data FASIH,
 * sehingga rekap per wilayah dan per mitra memakai satu tampilan.
 *
 * Satu ruta hasil alokasi = satu beban; hanya ruta yang sudah dialokasikan (punya wilayah) yang dihitung.
 * Submit = entri Selesai, Draft = entri yang disimpan mitra namun belum lengkap, Open = ruta yang belum disentuh.
 */
class PapiProgressRows
{
    public const UNMAPPED_DISTRICT = '3101000';

    /**
     * @return Collection<int, FasihProgressRow>
     */
    public function forSurvey(Survey $survey): Collection
    {
        $assignments = $survey->assignments()
            ->with(['mitra', 'entries' => fn ($query) => $query->with(['district', 'village'])])
            ->get();

        return $assignments->flatMap(fn (SurveyAssignment $assignment): Collection => $this->rowsForAssignment($assignment))->values();
    }

    /**
     * @return Collection<int, FasihProgressRow>
     */
    private function rowsForAssignment(SurveyAssignment $assignment): Collection
    {
        $rows = $assignment->entries
            ->groupBy(fn (SurveyEntry $entry): string => $this->slsCode($entry))
            ->map(function (Collection $entries, string $slsCode) use ($assignment): FasihProgressRow {
                /** @var SurveyEntry $first */
                $first = $entries->first();
                $submitted = $entries->where('entry_status', SurveyEntry::STATUS_SUBMITTED)->count();
                $drafted = $entries->where('entry_status', SurveyEntry::STATUS_DRAFT)->count();

                return $this->makeRow($assignment, [
                    'district_code' => $this->districtCode($first),
                    'village_code' => $this->villageCode($first),
                    'sls_code' => $slsCode,
                    'sls_label' => $this->slsLabel($first),
                    'total_region' => $entries->count(),
                    'submit_count' => $submitted,
                    'draft_count' => $drafted,
                    'open_count' => $entries->count() - $submitted - $drafted,
                ]);
            })
            ->values();

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeRow(SurveyAssignment $assignment, array $attributes): FasihProgressRow
    {
        $row = new FasihProgressRow([
            'user_id' => $assignment->mitra_id,
            'username' => $assignment->mitra?->email,
            'pencacah_name' => $assignment->mitra?->name,
            'email' => strtolower((string) $assignment->mitra?->email),
            'region_code' => $attributes['sls_code'],
            ...array_diff_key($attributes, ['sls_label' => true]),
        ]);
        $row->sls_label = $attributes['sls_label'];

        return $row;
    }

    private function districtCode(SurveyEntry $entry): string
    {
        return $entry->district?->code ?? self::UNMAPPED_DISTRICT;
    }

    private function villageCode(SurveyEntry $entry): string
    {
        return $entry->village?->code ?? $this->districtCode($entry).'000';
    }

    /**
     * SLS berupa 4 digit kode dipetakan ke master SLS; teks bebas dibuatkan kode turunan dari namanya.
     */
    private function slsCode(SurveyEntry $entry): string
    {
        $sls = trim((string) $entry->sls);

        return match (true) {
            preg_match('/^\d{4}$/', $sls) === 1 => $this->villageCode($entry).$sls,
            $sls === '' => $this->villageCode($entry).'0000',
            default => $this->villageCode($entry).'-'.Str::slug($sls),
        };
    }

    private function slsLabel(SurveyEntry $entry): ?string
    {
        $sls = trim((string) $entry->sls);

        return match (true) {
            preg_match('/^\d{4}$/', $sls) === 1 => null,
            $sls === '' => 'SLS belum diisi',
            default => $sls,
        };
    }
}
