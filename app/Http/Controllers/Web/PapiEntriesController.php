<?php

namespace App\Http\Controllers\Web;

use App\Exports\PapiEntriesExport;
use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Models\SurveyEntry;
use App\Models\SurveyVariable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Data Entri PAPI: isian mitra (identitas ruta, variabel, foto) per survei PAPI.
 * Dipakai admin dan pegawai (lihat saja), lengkap dengan filter, urutan, dan ekspor Excel/CSV.
 */
class PapiEntriesController extends Controller
{
    /**
     * @var array<string, string>
     */
    public const STATUS_TABS = [
        'all' => 'Semua',
        SurveyEntry::STATUS_SUBMITTED => 'Selesai',
        SurveyEntry::STATUS_DRAFT => 'Draft',
        SurveyEntry::STATUS_OPEN => 'Belum diisi',
    ];

    /**
     * Kolom yang bisa diurutkan => ekspresi SQL.
     *
     * @var array<string, string>
     */
    private const SORTABLE = [
        'updated' => 'survey_entries.updated_at',
        'submitted' => 'survey_entries.submitted_at',
        'mitra' => 'entry_mitra.name',
        'kecamatan' => 'entry_district.name',
        'kelurahan' => 'entry_village.name',
        'sls' => 'survey_entries.sls',
        'ruta' => 'CAST(survey_entries.no_urut_ruta AS DECIMAL(10,0))',
        'status' => 'survey_entries.entry_status',
    ];

    public function index(Request $request): View
    {
        $state = $this->state($request);
        $survey = $state['survey'];

        return view('panel.papi-entries', [
            ...$this->panel($request),
            ...$state,
            'statusTabs' => self::STATUS_TABS,
            'statusCounts' => $survey ? $this->statusCounts($survey, $state['filters']) : collect(),
            'entries' => $survey
                ? $this->sorted($this->entriesQuery($survey, $state['filters']), $state, $survey)->paginate(25)->withQueryString()
                : null,
        ]);
    }

    public function show(Request $request, SurveyEntry $entry): View
    {
        $survey = $entry->survey;
        abort_if(! $survey || $survey->isCapi(), 404);

        return view('panel.survey-entry-detail', [
            ...$this->panel($request),
            'survey' => $survey->load('variables'),
            'entry' => $entry->load(['assignment.mitra', 'district', 'village', 'values']),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $state = $this->state($request);
        $survey = $state['survey'];
        abort_unless($survey, 404);

        $format = $request->validate(['format' => ['nullable', 'in:xlsx,csv']])['format'] ?? 'xlsx';
        $variables = $survey->variables;
        $entries = $this->sorted($this->entriesQuery($survey, $state['filters']), $state, $survey)->get();

        $rows = $entries->values()->map(function (SurveyEntry $entry, int $index) use ($variables): array {
            $values = $entry->values->pluck('value', 'survey_variable_id');

            return [
                $index + 1,
                $entry->statusLabel(),
                $entry->assignment?->mitra?->name ?? '',
                $entry->assignment?->mitra?->email ?? '',
                $entry->district?->name ?? '',
                $entry->village?->name ?? '',
                $entry->sls ?? '',
                $this->numericOrText($entry->no_urut_ruta),
                ...$variables->map(fn (SurveyVariable $variable) => $variable->data_type === 'number'
                    ? $this->numericOrText($values->get($variable->id))
                    : (string) $values->get($variable->id, '')),
                $entry->submitted_at?->format('d/m/Y H:i') ?? '',
                $entry->updated_at?->format('d/m/Y H:i') ?? '',
                $entry->evidence_photo_path ? asset('storage/'.$entry->evidence_photo_path) : '',
            ];
        });

        $headings = [
            'No', 'Status', 'Mitra', 'Email Mitra', 'Kecamatan', 'Desa/Kelurahan', 'SLS', 'No Urut Ruta',
            ...$variables->pluck('name')->all(),
            'Waktu Dikirim', 'Terakhir Diubah', 'Foto Bukti',
        ];

        $status = $state['filters']['status'];
        $fileName = 'entri-papi-'.Str::slug($survey->title).($status !== 'all' ? '-'.Str::slug(self::STATUS_TABS[$status]) : '').'-'.now()->format('Ymd-Hi').'.'.$format;

        return Excel::download(
            new PapiEntriesExport($rows, $headings, $survey->title, $variables->count()),
            $fileName,
            $format === 'csv' ? ExcelFormat::CSV : ExcelFormat::XLSX,
        );
    }

    /**
     * Survei terpilih, filter, dan urutan dari query string.
     *
     * @return array{surveys: Collection<int, Survey>, survey: Survey|null, filters: array{status: string, q: string, from: ?string, to: ?string}, sort: string, dir: string}
     */
    private function state(Request $request): array
    {
        $data = $request->validate([
            'survey' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:'.implode(',', array_keys(self::STATUS_TABS))],
            'q' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'sort' => ['nullable', 'string', 'max:30'],
            'dir' => ['nullable', 'in:asc,desc'],
        ], [
            'to.after_or_equal' => 'Tanggal akhir harus sama atau setelah tanggal awal.',
        ]);

        $surveys = Survey::query()->where('type', Survey::TYPE_PAPI)
            ->orderByRaw("case status when 'Berjalan' then 0 when 'Draft' then 1 else 2 end")
            ->latest('id')
            ->get(['id', 'title', 'type', 'status', 'start_date', 'end_date']);

        $survey = isset($data['survey']) ? $surveys->firstWhere('id', (int) $data['survey']) : $surveys->first();
        $survey?->load('variables');

        $sort = $data['sort'] ?? 'updated';
        $isVariableSort = $survey && preg_match('/^var_(\d+)$/', $sort, $match) && $survey->variables->contains('id', (int) $match[1]);
        if (! $isVariableSort && ! array_key_exists($sort, self::SORTABLE)) {
            $sort = 'updated';
        }

        return [
            'surveys' => $surveys,
            'survey' => $survey,
            'filters' => [
                'status' => $data['status'] ?? SurveyEntry::STATUS_SUBMITTED,
                'q' => trim($data['q'] ?? ''),
                'from' => $data['from'] ?? null,
                'to' => $data['to'] ?? null,
            ],
            'sort' => $sort,
            'dir' => $data['dir'] ?? ($sort === 'updated' || $sort === 'submitted' ? 'desc' : 'asc'),
        ];
    }

    /**
     * @param  array{status: string, q: string, from: ?string, to: ?string}  $filters
     * @return Builder<SurveyEntry>
     */
    private function entriesQuery(Survey $survey, array $filters, bool $withStatus = true): Builder
    {
        $search = $filters['q'];

        return SurveyEntry::query()
            ->select('survey_entries.*')
            ->join('survey_assignments as entry_assignment', 'entry_assignment.id', '=', 'survey_entries.survey_assignment_id')
            ->join('users as entry_mitra', 'entry_mitra.id', '=', 'entry_assignment.mitra_id')
            ->leftJoin('villages as entry_village', 'entry_village.id', '=', 'survey_entries.village_id')
            ->leftJoin('districts as entry_district', 'entry_district.id', '=', 'survey_entries.district_id')
            ->with(['assignment.mitra', 'district', 'village', 'values'])
            ->where('survey_entries.survey_id', $survey->id)
            ->when($withStatus && $filters['status'] !== 'all', fn (Builder $query) => $query->where('survey_entries.entry_status', $filters['status']))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('entry_mitra.name', 'like', '%'.$search.'%')
                ->orWhere('entry_mitra.email', 'like', '%'.$search.'%')
                ->orWhere('survey_entries.sls', 'like', '%'.$search.'%')
                ->orWhere('survey_entries.no_urut_ruta', $search)
                ->orWhere('entry_village.name', 'like', '%'.$search.'%')))
            ->when($filters['from'], fn (Builder $query, string $from) => $query->where('survey_entries.updated_at', '>=', $from.' 00:00:00'))
            ->when($filters['to'], fn (Builder $query, string $to) => $query->where('survey_entries.updated_at', '<=', $to.' 23:59:59'));
    }

    /**
     * @param  Builder<SurveyEntry>  $query
     * @param  array{sort: string, dir: string}  $state
     * @return Builder<SurveyEntry>
     */
    private function sorted(Builder $query, array $state, Survey $survey): Builder
    {
        $direction = $state['dir'];

        if (str_starts_with($state['sort'], 'var_')) {
            $variable = $survey->variables->firstWhere('id', (int) substr($state['sort'], 4));
            $query->leftJoin('entry_variable_values as sort_value', function ($join) use ($variable): void {
                $join->on('sort_value.survey_entry_id', '=', 'survey_entries.id')->where('sort_value.survey_variable_id', '=', $variable->id);
            });
            $expression = $variable->data_type === 'number' ? 'CAST(sort_value.value AS DECIMAL(18,4))' : 'sort_value.value';
        } else {
            $expression = self::SORTABLE[$state['sort']];
        }

        return $query->orderByRaw($expression.' '.$direction)->orderBy('survey_entries.id', $direction);
    }

    /**
     * Jumlah entri per status dengan filter pencarian dan tanggal yang sama.
     *
     * @param  array{status: string, q: string, from: ?string, to: ?string}  $filters
     * @return Collection<string, int>
     */
    private function statusCounts(Survey $survey, array $filters): Collection
    {
        $counts = $this->entriesQuery($survey, $filters, withStatus: false)
            ->reorder()
            ->setEagerLoads([])
            ->select('survey_entries.entry_status', DB::raw('count(*) as total'))
            ->groupBy('survey_entries.entry_status')
            ->pluck('total', 'entry_status')
            ->map(fn ($total): int => (int) $total);

        return $counts->put('all', (int) $counts->sum());
    }

    /**
     * Angka ditulis sebagai angka agar bisa langsung dijumlah di Excel; selain itu tetap teks.
     */
    private function numericOrText(mixed $value): int|float|string
    {
        $text = trim((string) $value);
        if ($text !== '' && preg_match('/^-?\d+(\.\d+)?$/', $text)) {
            return str_contains($text, '.') ? (float) $text : (int) $text;
        }

        return $text;
    }

    /**
     * @return array{panelTitle: string, menuView: string, base: string}
     */
    private function panel(Request $request): array
    {
        return $request->user()->hasRole('admin')
            ? ['panelTitle' => 'Admin', 'menuView' => 'panel.admin.menu', 'base' => '/admin']
            : ['panelTitle' => 'Pegawai BPS', 'menuView' => 'panel.pegawai.menu', 'base' => '/pegawai'];
    }
}
