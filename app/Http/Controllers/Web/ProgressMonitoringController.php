<?php

namespace App\Http\Controllers\Web;

use App\Exports\EntriesExport;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\District;
use App\Models\FasihImport;
use App\Models\FasihProgressRow;
use App\Models\Survey;
use App\Models\SurveyCheckpoint;
use App\Models\SurveyEntry;
use App\Services\FasihProgressImporter;
use App\Services\FasihProgressReport;
use App\Services\MitraCheckpointProgress;
use App\Services\PapiProgressRows;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Monitoring progres per survei. Survei CAPI membaca snapshot import FASIH, survei PAPI membaca
 * alokasi dan entri mitra. Admin dapat mengimpor/menghapus data FASIH; pegawai hanya melihat.
 */
class ProgressMonitoringController extends Controller
{
    public function __construct(
        private readonly FasihProgressReport $report,
        private readonly PapiProgressRows $papiRows,
        private readonly MitraCheckpointProgress $checkpointProgress,
    ) {}

    public function index(Request $request): View
    {
        $isAdmin = $request->user()->hasRole('admin');

        return view('panel.monitoring-progres', [
            'surveys' => $this->runningSurveys()->get(['id', 'title', 'type', 'status']),
            'canManage' => $isAdmin,
            'basePath' => $isAdmin ? '/admin/monitoring/progres' : '/pegawai/monitoring/progres',
            'panelTitle' => $isAdmin ? 'Admin' : 'Pegawai BPS',
            'menuView' => $isAdmin ? 'panel.admin.menu' : 'panel.pegawai.menu',
            ...$this->buildReport($request),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $data = $this->buildReport($request);
        abort_unless($data['hasData'], 404);

        $isMitraLevel = $data['level'] === 'mitra';
        $rows = $data['groups']->map(fn (array $group): array => [
            ...($isMitraLevel
                ? [$group['pencacah'] ?? '', $group['context'], $group['sls_count']]
                : [$group['code'], $group['name'], implode(', ', $group['mitra'])]),
            $group['total'],
            $group['submit'],
            $group['draft'],
            $group['open'],
            $group['other'],
            $group['percent'],
        ]);

        $headings = [
            ...($isMitraLevel
                ? ['Nama Pencacah', 'Mitra (email)', 'Jumlah SLS']
                : ['Kode', FasihProgressReport::LEVELS[$data['level']], 'Mitra']),
            'Beban', 'Submit', 'Draft', 'Open', 'Lainnya', 'Progres Submit (%)',
        ];

        $fileName = 'progres-'.Str::slug($data['selectedSurvey']->title).'-per-'.$data['level'].'-'.now()->format('Ymd-Hi').'.xlsx';

        return Excel::download(new EntriesExport($rows, $headings), $fileName);
    }

    public function store(Request $request, FasihProgressImporter $importer): RedirectResponse
    {
        $request->validate([
            'survey_id' => ['required', 'integer', 'exists:surveys,id'],
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ], [
            'file.required' => 'Pilih file CSV hasil scraping FASIH terlebih dahulu.',
            'file.mimes' => 'File harus berformat CSV.',
            'file.max' => 'Ukuran file maksimal 10 MB.',
        ]);

        $survey = Survey::query()->findOrFail($request->integer('survey_id'));
        abort_unless($survey->isCapi(), 422, 'Data FASIH hanya dapat diimpor ke survei CAPI.');
        abort_unless($survey->status === 'Berjalan', 422, 'Data FASIH hanya dapat diimpor ke survei yang sedang berjalan.');

        $fasihImport = $importer->import($request->file('file'), $request->user(), $survey);

        ActivityLog::query()->create([
            'user_id' => $request->user()?->id,
            'action' => 'admin.fasih.import',
            'description' => "Mengimpor progres FASIH {$fasihImport->file_name} ({$fasihImport->row_count} baris) untuk survei {$survey->title}",
        ]);

        return redirect('/admin/monitoring/progres?survey='.$survey->id)
            ->with('status', "Import berhasil: {$fasihImport->row_count} baris dari {$fasihImport->file_name}.");
    }

    public function destroy(Request $request, FasihImport $fasihImport): RedirectResponse
    {
        $fileName = $fasihImport->file_name;
        $survey = $fasihImport->survey;
        $fasihImport->delete();
        $survey?->recalculateTarget();

        ActivityLog::query()->create([
            'user_id' => $request->user()?->id,
            'action' => 'admin.fasih.delete',
            'description' => "Menghapus data import FASIH {$fileName}",
        ]);

        return redirect('/admin/monitoring/progres'.($survey ? '?survey='.$survey->id : ''))
            ->with('status', "Data import {$fileName} dihapus.");
    }

    /**
     * Monitoring hanya memantau survei yang sedang berjalan.
     *
     * @return Builder<Survey>
     */
    private function runningSurveys(): Builder
    {
        return Survey::query()->where('status', 'Berjalan')->latest();
    }

    /**
     * Cakupan tampilan ditentukan oleh survei, mode (wilayah/mitra), dan filter wilayah dari breadcrumb.
     *
     * @return array<string, mixed>
     */
    private function buildReport(Request $request): array
    {
        $filters = $request->validate([
            'survey' => ['nullable', 'integer', 'exists:surveys,id'],
            'import' => ['nullable', 'integer'],
            'mode' => ['nullable', 'in:wilayah,mitra'],
            'kecamatan' => ['nullable', 'string', 'size:7'],
            'desa' => ['nullable', 'string', 'size:10'],
            'sls' => ['nullable', 'string', 'max:160'],
            'mitra' => ['nullable', 'string', 'max:255'],
            'minggu' => ['nullable', 'integer', 'min:0', 'max:520'],
        ]);

        // Survei Draft dan yang sudah ditandai selesai tidak ikut dipantau; pilihan lain jatuh ke survei berjalan terbaru.
        $selectedSurvey = (isset($filters['survey']) ? $this->runningSurveys()->find($filters['survey']) : null)
            ?? $this->runningSurveys()->first();

        $imports = $selectedSurvey?->isCapi()
            ? $selectedSurvey->fasihImports()->with('user')->latest('id')->get()
            : collect();
        $selectedImport = isset($filters['import']) ? $imports->firstWhere('id', (int) $filters['import']) : $imports->first();
        abort_if(isset($filters['import']) && ! $selectedImport, 404);

        /** @var Collection<int, FasihProgressRow> $allRows */
        $allRows = match (true) {
            $selectedSurvey === null => collect(),
            $selectedSurvey->isCapi() => $selectedImport ? $selectedImport->rows()->get() : collect(),
            default => $this->papiRows->forSurvey($selectedSurvey),
        };

        // Wilayah di luar master (mis. kode tingkat kabupaten dari FASIH) tidak ditampilkan sebagai baris.
        // Untuk CAPI baris itu dibuang; untuk PAPI isinya sisa target tanpa wilayah yang tetap dihitung di total.
        $knownDistricts = District::query()->pluck('code')->flip();
        $isMapped = fn (FasihProgressRow $row): bool => $knownDistricts->has($row->district_code);
        if ($selectedSurvey?->isCapi()) {
            $allRows = $allRows->filter($isMapped)->values();
        }
        $this->report->registerSlsLabels($allRows);

        $mode = $filters['mode'] ?? 'wilayah';
        $level = match (true) {
            $mode === 'mitra' => empty($filters['mitra']) ? 'mitra' : 'sls',
            ! empty($filters['sls']) => 'mitra',
            ! empty($filters['desa']) => 'sls',
            ! empty($filters['kecamatan']) => 'desa',
            default => 'kecamatan',
        };

        $scopedRows = $this->applyFilters($allRows, $filters);
        $groupRows = $mode === 'wilayah' ? $scopedRows->filter($isMapped)->values() : $scopedRows;
        $mitraNames = $this->report->groupBy($allRows, 'mitra')->pluck('name', 'key');

        // Tampilan wilayah bisa dibuka bertingkat dari tingkat saat ini sampai SLS; mitra tiap SLS tampil di kolomnya.
        $treeLevels = ['kecamatan', 'desa', 'sls'];
        // Tampilan mitra: setiap mitra bisa dibuka untuk melihat SLS yang dipegangnya.
        $groups = match (true) {
            $mode === 'wilayah' && in_array($level, $treeLevels, true) => $this->report->tree($groupRows, array_slice($treeLevels, array_search($level, $treeLevels, true))),
            $mode === 'mitra' && $level === 'mitra' => $this->report->tree($groupRows, ['mitra', 'sls']),
            default => $this->report->groupBy($groupRows, $level),
        };

        return [
            'selectedSurvey' => $selectedSurvey,
            'imports' => $imports,
            'selectedImport' => $selectedImport,
            'isLatestImport' => $selectedImport?->is($imports->first()) ?? false,
            'hasData' => $allRows->isNotEmpty(),
            'mode' => $mode,
            'level' => $level,
            'filters' => $filters,
            'totals' => $this->report->totals($scopedRows),
            'groups' => $groups,
            'breadcrumbs' => $this->breadcrumbs($filters, $mode, $mitraNames),
            ...$this->dailyEntries($selectedSurvey, (int) ($filters['minggu'] ?? 0)),
            ...$this->checkpointStatus($selectedSurvey),
        ];
    }

    /**
     * Jumlah entri PAPI yang dikirim mitra per hari pada satu survei, satu minggu kalender
     * (Senin sampai Minggu) per tampilan. Minggu 0 memuat hari ini (atau akhir periode survei);
     * minggu berikutnya mundur satu minggu sampai minggu awal periode survei.
     *
     * @return array{dailyEntries: Collection<int, array{date: Carbon, count: int}>, dailyWeek: int, dailyMaxWeek: int}
     */
    private function dailyEntries(?Survey $survey, int $weeksBack): array
    {
        $empty = ['dailyEntries' => collect(), 'dailyWeek' => 0, 'dailyMaxWeek' => 0];
        if (! $survey || $survey->isCapi()) {
            return $empty;
        }

        $timezone = 'Asia/Jakarta';
        $periodStart = Carbon::parse($survey->start_date->toDateString(), $timezone);
        $latest = now($timezone)->startOfDay()->min(Carbon::parse($survey->end_date->toDateString(), $timezone));
        if ($periodStart->gt($latest)) {
            return $empty;
        }

        $latestWeekStart = $latest->copy()->startOfWeek(Carbon::MONDAY);
        $maxWeek = intdiv((int) $periodStart->copy()->startOfWeek(Carbon::MONDAY)->diffInDays($latestWeekStart), 7);
        $week = min($weeksBack, $maxWeek);
        $start = $latestWeekStart->copy()->subDays($week * 7);
        $end = $start->copy()->addDays(6);

        $counts = SurveyEntry::query()
            ->where('survey_id', $survey->id)
            ->where('entry_status', SurveyEntry::STATUS_SUBMITTED)
            ->where('submitted_at', '>=', $start->copy()->timezone(config('app.timezone')))
            ->where('submitted_at', '<', $end->copy()->addDay()->timezone(config('app.timezone')))
            ->pluck('submitted_at')
            ->countBy(fn (Carbon $submittedAt): string => $submittedAt->copy()->timezone($timezone)->toDateString());

        return [
            'dailyEntries' => collect(range(0, (int) $start->diffInDays($end)))->map(function (int $offset) use ($start, $counts): array {
                $date = $start->copy()->addDays($offset);

                return ['date' => $date, 'count' => (int) $counts->get($date->toDateString(), 0)];
            }),
            'dailyWeek' => $week,
            'dailyMaxWeek' => $maxWeek,
        ];
    }

    /**
     * Checkpoint terakhir yang sudah lewat beserta mitra yang capaiannya masih di bawah targetnya,
     * diurutkan dari capaian terendah.
     *
     * @return array{passedCheckpoint: SurveyCheckpoint|null, belowCheckpoint: Collection<int, array{name: string, progress: int, target: int, percent: float}>}
     */
    private function checkpointStatus(?Survey $survey): array
    {
        $checkpoint = $survey?->passedCheckpoint();

        return [
            'passedCheckpoint' => $checkpoint,
            'belowCheckpoint' => $checkpoint ? $this->checkpointProgress->below($survey, $checkpoint) : collect(),
        ];
    }

    /**
     * @param  Collection<int, FasihProgressRow>  $rows
     * @param  array<string, mixed>  $filters
     * @return Collection<int, FasihProgressRow>
     */
    private function applyFilters(Collection $rows, array $filters): Collection
    {
        return $rows
            ->when($filters['kecamatan'] ?? null, fn (Collection $rows, string $code) => $rows->where('district_code', $code))
            ->when($filters['desa'] ?? null, fn (Collection $rows, string $code) => $rows->where('village_code', $code))
            ->when($filters['sls'] ?? null, fn (Collection $rows, string $code) => $rows->where('sls_code', $code))
            ->when($filters['mitra'] ?? null, fn (Collection $rows, string $email) => $rows->where('email', strtolower($email)))
            ->values();
    }

    /**
     * Jejak cakupan: tiap langkah berisi label dan parameter URL untuk kembali ke langkah itu.
     *
     * @param  array<string, mixed>  $filters
     * @param  Collection<string, string>  $mitraNames
     * @return list<array{label: string, params: array<string, string>}>
     */
    private function breadcrumbs(array $filters, string $mode, Collection $mitraNames): array
    {
        $modeParams = $mode === 'mitra' ? ['mode' => 'mitra'] : [];
        $crumbs = [['label' => $mode === 'mitra' ? 'Semua mitra' : 'Kab. Kepulauan Seribu', 'params' => $modeParams]];
        $scope = [];

        foreach (['kecamatan' => 'districtName', 'desa' => 'villageName', 'sls' => 'slsName'] as $key => $nameResolver) {
            if (empty($filters[$key])) {
                break;
            }
            $scope[$key] = $filters[$key];
            $crumbs[] = ['label' => $this->report->{$nameResolver}($filters[$key]), 'params' => [...$modeParams, ...$scope]];
        }

        if ($mode === 'mitra' && ! empty($filters['mitra'])) {
            $email = strtolower($filters['mitra']);
            $crumbs[] = ['label' => $mitraNames->get($email, $email), 'params' => [...$modeParams, ...$scope, 'mitra' => $email]];
        }

        return $crumbs;
    }
}
