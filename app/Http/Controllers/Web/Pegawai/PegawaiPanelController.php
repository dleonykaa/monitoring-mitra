<?php

namespace App\Http\Controllers\Web\Pegawai;

use App\Exports\AssignmentTemplateExport;
use App\Exports\EntriesExport;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\District;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyCheckpoint;
use App\Models\SurveyEntry;
use App\Models\SurveyVariable;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class PegawaiPanelController extends Controller
{
    private const CHECKPOINT_ALERT_GAP_PERCENTAGE = 10;

    public function dashboard(Request $request): View
    {
        $teamIds = $this->teamIds($request);
        $teams = Team::query()->whereIn('id', $teamIds)->get();
        $surveyIds = Survey::query()->whereIn('team_id', $teamIds)->pluck('id');

        // Daftar survei tim untuk dropdown "Pilih Survei"
        $teamSurveys = Survey::query()->whereIn('id', $surveyIds)->orderByDesc('created_at')->get();

        $selectedSurveyId = $request->integer('survey_id') ?: null;
        if ($selectedSurveyId && ! $surveyIds->contains($selectedSurveyId)) {
            $selectedSurveyId = null; // abaikan survei di luar tim
        }
        // Lingkup data: satu survei bila dipilih, atau seluruh survei tim
        $scopeIds = $selectedSurveyId ? collect([$selectedSurveyId]) : $surveyIds;

        $totalTarget = (int) Survey::query()->whereIn('id', $scopeIds)->sum('total_target');
        $totalProgress = (int) SurveyAssignment::query()->whereIn('survey_id', $scopeIds)->sum('current_progress');
        $selectedMitraId = $request->integer('mitra_id') ?: null;
        $assignmentRows = SurveyAssignment::query()
            ->with(['survey.checkpoints', 'mitra'])
            ->whereIn('survey_id', $scopeIds)
            ->get();
        $checkpointAssignments = $assignmentRows
            ->map(function (SurveyAssignment $assignment): ?object {
                if ($assignment->survey->status !== 'Berjalan' || $assignment->target < 1) {
                    return null;
                }

                $checkpoint = $assignment->survey->checkpoints
                    ->filter(fn (SurveyCheckpoint $checkpoint): bool => $checkpoint->checkpoint_date->lte(today()))
                    ->last();

                if (! $checkpoint) {
                    return null;
                }

                $checkpointNumber = $assignment->survey->checkpoints->search(
                    fn (SurveyCheckpoint $c): bool => $c->id === $checkpoint->id
                ) + 1;

                $progressPercentage = round(($assignment->current_progress / $assignment->target) * 100, 1);
                $gapPercentage = round($checkpoint->target_percentage - $progressPercentage, 1);

                if ($gapPercentage < self::CHECKPOINT_ALERT_GAP_PERCENTAGE) {
                    return null;
                }

                $checkpointTarget = (int) ceil($assignment->target * $checkpoint->target_percentage / 100);

                return (object) [
                    'assignment' => $assignment,
                    'checkpoint' => $checkpoint,
                    'checkpointNumber' => $checkpointNumber,
                    'progressPercentage' => $progressPercentage,
                    'gapPercentage' => $gapPercentage,
                    'shortfall' => max(0, $checkpointTarget - $assignment->current_progress),
                ];
            })
            ->filter()
            ->sortByDesc('gapPercentage')
            ->values();
        $mitraMonitoring = $assignmentRows
            ->groupBy('mitra_id')
            ->map(function (Collection $assignments): object {
                $mitra = $assignments->first()->mitra;
                $target = (int) $assignments->sum('target');
                $progress = (int) $assignments->sum('current_progress');

                return (object) [
                    'mitra' => $mitra,
                    'target' => $target,
                    'progress' => $progress,
                    'percentage' => $target > 0 ? min(100, round(($progress / $target) * 100, 1)) : 0,
                    'survey_count' => $assignments->count(),
                    'running_count' => $assignments->filter(fn (SurveyAssignment $assignment): bool => $assignment->survey->status === 'Berjalan')->count(),
                ];
            })
            ->sortByDesc('progress')
            ->values();

        return view('panel.pegawai.dashboard', [
            'teams' => $teams,
            'teamSurveys' => $teamSurveys,
            'selectedSurveyId' => $selectedSurveyId,
            'selectedSurvey' => $selectedSurveyId ? $teamSurveys->firstWhere('id', $selectedSurveyId) : null,
            'totalSurveys' => $selectedSurveyId ? 1 : $surveyIds->count(),
            'totalTarget' => $totalTarget,
            'totalProgress' => $totalProgress,
            'overallPercentage' => $totalTarget > 0 ? round(($totalProgress / $totalTarget) * 100, 1) : 0,
            'lateAssignments' => SurveyAssignment::query()
                ->whereIn('survey_id', $scopeIds)
                ->whereColumn('current_progress', '<', 'target')
                ->whereHas('survey', fn ($q) => $q->whereDate('end_date', '<', now()->toDateString()))
                ->count(),
            'progressByDistrict' => SurveyEntry::query()
                ->select('district_id', DB::raw('count(*) as total'))
                ->whereIn('survey_id', $scopeIds)
                ->where('entry_status', 'submitted')
                ->groupBy('district_id')
                ->with('district')
                ->get(),
            'surveyProgress' => Survey::query()
                ->withSum('assignments as progress_sum', 'current_progress')
                ->whereIn('id', $scopeIds)
                ->latest()
                ->get(),
            'mitraUsers' => User::query()
                ->role('mitra')
                ->whereHas('assignments', fn ($query) => $query->whereIn('survey_id', $scopeIds))
                ->orderBy('name')
                ->get(),
            'selectedMitraId' => $selectedMitraId,
            'mitraSearch' => '',
            'mitraMonitoring' => $mitraMonitoring,
            'mitraProgress' => SurveyAssignment::query()
                ->with(['survey', 'mitra'])
                ->whereIn('survey_id', $scopeIds)
                ->when($selectedMitraId, fn ($query) => $query->where('mitra_id', $selectedMitraId))
                ->orderByDesc('updated_at')
                ->get(),
            'staleAssignments' => SurveyAssignment::query()
                ->with(['survey', 'mitra'])
                ->withMax(['entries as last_entry_at' => fn ($query) => $query->where('entry_status', 'submitted')], 'created_at')
                ->whereIn('survey_id', $scopeIds)
                ->whereColumn('current_progress', '<', 'target')
                ->whereHas('survey', fn ($query) => $query->where('status', 'Berjalan'))
                ->get()
                ->filter(fn (SurveyAssignment $assignment): bool => $assignment->last_entry_at === null || \Illuminate\Support\Carbon::parse($assignment->last_entry_at)->lte(now()->subDays(3)))
                ->values(),
            'checkpointAssignments' => $checkpointAssignments,
        ]);
    }

    public function surveys(Request $request): View
    {
        $teamIds = $this->teamIds($request);
        $this->syncSurveyStatuses($teamIds);

        return view('panel.pegawai.surveys', [
            'runningSurveys' => Survey::query()
                ->with(['team'])
                ->withCount('entries')
                ->whereIn('team_id', $teamIds)
                ->where('status', 'Berjalan')
                ->latest()
                ->get(),
            'draftSurveys' => Survey::query()
                ->with(['team'])
                ->withCount('entries')
                ->whereIn('team_id', $teamIds)
                ->where('status', 'Draft')
                ->latest()
                ->get(),
            'completedSurveys' => Survey::query()
                ->with(['team'])
                ->withCount('entries')
                ->whereIn('team_id', $teamIds)
                ->where('status', 'Selesai')
                ->latest()
                ->get(),
            'teams' => $request->user()->teams,
        ]);
    }

    public function createSurvey(Request $request): View
    {
        return view('panel.pegawai.survey-create', [
            'teams' => $request->user()->teams,
        ]);
    }

    public function showSurvey(Request $request, Survey $survey): View
    {
        $this->authorizeSurveyAccess($request, $survey);
        $survey->load([
            'variables',
            'entries',
            'assignments' => fn ($query) => $query
                ->with('mitra')
                ->orderByDesc('current_progress')
                ->orderByDesc('target'),
        ]);

        return view('panel.pegawai.survey-detail', [
            'survey' => $survey,
            'mitraUsers' => User::query()->role('mitra')->orderBy('name')->get(),
        ]);
    }

    public function editSurvey(Request $request, Survey $survey): View
    {
        $this->authorizeSurveyAccess($request, $survey);
        $survey->load([
            'variables',
            'entries',
            'checkpoints',
            'assignments' => fn ($query) => $query
                ->with('mitra')
                ->orderByDesc('current_progress')
                ->orderByDesc('target'),
        ]);

        return view('panel.pegawai.survey-edit', [
            'survey' => $survey,
            'mitraUsers' => User::query()->role('mitra')->orderBy('name')->get(),
        ]);
    }

    public function storeSurvey(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'team_id' => ['required', 'exists:teams,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        abort_unless($request->user()->teams()->whereKey($data['team_id'])->exists(), 403);

        // Total target dihitung dari akumulasi target per mitra (lihat recalculateTarget).
        // Survei baru selalu berstatus Draft sampai dijalankan oleh pegawai.
        $survey = Survey::query()->create($data + [
            'created_by' => $request->user()->id,
            'status' => 'Draft',
            'total_target' => 0,
        ]);
        ActivityLog::query()->create(['user_id' => $request->user()->id, 'action' => 'pegawai.survey.create', 'description' => 'Membuat survei '.$data['title']]);

        return redirect('/pegawai/surveys/'.$survey->id.'/variables');
    }

    public function updateSurvey(Request $request, Survey $survey): RedirectResponse
    {
        $this->authorizeSurveyAccess($request, $survey);
        $this->ensureSurveyIsEditable($survey);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $survey->update($data);
        $this->syncSurveyStatus($survey);

        ActivityLog::query()->create(['user_id' => $request->user()->id, 'action' => 'pegawai.survey.update', 'description' => 'Edit survei '.$survey->title]);

        return back()->with('status', 'Survei berhasil diperbarui.');
    }

    public function deleteSurvey(Request $request, Survey $survey): RedirectResponse
    {
        $this->authorizeSurveyAccess($request, $survey);
        $this->ensureSurveyIsEditable($survey);

        $title = $survey->title;
        $survey->delete();

        ActivityLog::query()->create(['user_id' => $request->user()->id, 'action' => 'pegawai.survey.delete', 'description' => 'Menghapus survei '.$title]);

        return redirect('/pegawai/surveys')->with('status', 'Survei berhasil dihapus.');
    }

    public function variables(Request $request, Survey $survey): View|RedirectResponse
    {
        $this->authorizeSurveyAccess($request, $survey);

        if ($survey->status === 'Selesai') {
            return redirect('/pegawai/surveys/'.$survey->id)->withErrors(['survey' => 'Survei selesai tidak dapat diedit.']);
        }

        return view('panel.pegawai.survey-variables', [
            'survey' => $survey->load('variables'),
        ]);
    }

    public function storeVariable(Request $request, Survey $survey): RedirectResponse
    {
        $this->authorizeSurveyAccess($request, $survey);
        $this->ensureSurveyIsEditable($survey);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'data_type' => ['required', 'in:text,number'],
            'example_format' => ['nullable', 'string', 'max:255'],
        ]);

        // updateOrCreate berdasarkan nama -> klik dobel tidak menghasilkan duplikat.
        $survey->variables()->updateOrCreate(
            ['name' => $data['name']],
            ['data_type' => $data['data_type'], 'example_format' => $data['example_format'] ?? null]
        );

        return back()->with('status', 'Variabel validasi disimpan.');
    }

    public function downloadVariableTemplate(Request $request, Survey $survey)
    {
        $this->authorizeSurveyAccess($request, $survey);

        $variables = $survey->variables()->orderBy('id')->get();
        $baseColumns = ['ID', 'Mitra', 'Responden', 'Wilayah'];

        $rows = [
            array_merge(['', '', '', 'Progress Pencacahan '.$survey->title], array_fill(0, max(0, $variables->count() + 2), '')),
            array_merge($baseColumns, $variables->pluck('name')->all(), ['Status', 'Catatan']),
            array_merge(['1', 'Nama Mitra', 'Nama Responden', 'Kecamatan / Kelurahan'], $variables->pluck('example_format')->map(fn ($example) => $example ?: '-')->all(), ['Menunggu', 'Catatan validasi']),
        ];

        return Excel::download(new class($rows) implements \Maatwebsite\Excel\Concerns\FromArray {
            public function __construct(private readonly array $rows) {}
            public function array(): array { return $this->rows; }
        }, 'template-'.$survey->title.'.xlsx');
    }

    public function updateVariable(Request $request, Survey $survey, SurveyVariable $variable): RedirectResponse
    {
        $this->authorizeSurveyAccess($request, $survey);
        $this->ensureSurveyIsEditable($survey);
        abort_unless($variable->survey_id === $survey->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'data_type' => ['required', 'in:text,number'],
            'example_format' => ['nullable', 'string', 'max:255'],
        ]);

        $variable->update($data);

        return back()->with('status', 'Variabel validasi diperbarui.');
    }

    public function deleteVariable(Request $request, Survey $survey, SurveyVariable $variable): RedirectResponse
    {
        $this->authorizeSurveyAccess($request, $survey);
        $this->ensureSurveyIsEditable($survey);
        abort_unless($variable->survey_id === $survey->id, 404);

        $variable->delete();

        return back()->with('status', 'Variabel validasi dihapus.');
    }

    public function updateValidationFormula(Request $request, Survey $survey): RedirectResponse
    {
        $this->authorizeSurveyAccess($request, $survey);
        $this->ensureSurveyIsEditable($survey);

        $data = $request->validate([
            'validation_rules' => ['nullable', 'array', 'max:20'],
            'validation_rules.*' => ['nullable', 'string', 'max:500'],
        ]);
        $validationRules = collect($data['validation_rules'] ?? [])
            ->map(fn (?string $rule): string => trim((string) $rule))
            ->filter()
            ->values()
            ->all();

        $survey->update([
            'validation_formula' => null,
            'validation_rules' => $validationRules !== [] ? $validationRules : null,
        ]);

        return redirect("/pegawai/surveys/{$survey->id}/assignments")->with('status', 'Aturan validasi disimpan.');
    }

    public function assignments(Request $request, Survey $survey): View|RedirectResponse
    {
        $this->authorizeSurveyAccess($request, $survey);

        if ($survey->status === 'Selesai') {
            return redirect('/pegawai/surveys/'.$survey->id)->withErrors(['survey' => 'Survei selesai tidak dapat diedit.']);
        }

        return view('panel.pegawai.survey-assignments', [
            'survey' => $survey->load('assignments.mitra'),
            'mitraUsers' => User::query()->role('mitra')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function storeAssignment(Request $request, Survey $survey): RedirectResponse
    {
        $this->authorizeSurveyAccess($request, $survey);
        $this->ensureSurveyIsEditable($survey);

        $data = $request->validate([
            'mitra_id' => ['required', 'exists:users,id'],
            'target' => ['required', 'integer', 'min:1'],
        ]);

        SurveyAssignment::query()->updateOrCreate(
            ['survey_id' => $survey->id, 'mitra_id' => $data['mitra_id']],
            ['target' => $data['target']]
        );

        $this->recalculateTarget($survey);

        return back()->with('status', 'Mitra dialokasikan.');
    }

    public function updateAssignment(Request $request, Survey $survey, SurveyAssignment $assignment): RedirectResponse
    {
        $this->authorizeSurveyAccess($request, $survey);
        $this->ensureSurveyIsEditable($survey);
        abort_unless($assignment->survey_id === $survey->id, 404);

        $data = $request->validate([
            'target' => ['required', 'integer', 'min:1'],
        ]);

        $assignment->update(['target' => $data['target']]);
        $this->recalculateTarget($survey);

        return back()->with('status', 'Target mitra diperbarui.');
    }

    public function deleteAssignment(Request $request, Survey $survey, SurveyAssignment $assignment): RedirectResponse
    {
        $this->authorizeSurveyAccess($request, $survey);
        $this->ensureSurveyIsEditable($survey);
        abort_unless($assignment->survey_id === $survey->id, 404);

        $assignment->delete();
        $this->recalculateTarget($survey);

        return back()->with('status', 'Alokasi mitra dihapus.');
    }

    public function downloadAssignmentTemplate(Request $request, Survey $survey): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorizeSurveyAccess($request, $survey);

        return Excel::download(new AssignmentTemplateExport, 'template-alokasi-'.$survey->id.'.xlsx');
    }

    public function importAssignments(Request $request, Survey $survey): RedirectResponse
    {
        $this->authorizeSurveyAccess($request, $survey);
        $this->ensureSurveyIsEditable($survey);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt'],
            'import_mode' => ['required', 'in:append,replace'],
        ]);

        $mode = $request->string('import_mode')->value();
        $rows = Excel::toArray([], $request->file('file'))[0] ?? [];
        $header = array_map(fn ($cell) => trim((string) $cell), $rows[0] ?? []);
        $expected = AssignmentTemplateExport::HEADINGS;

        if (array_map('strtolower', array_slice($header, 0, count($expected))) !== array_map('strtolower', $expected)) {
            return back()->withErrors([
                'file' => 'Format template tidak sesuai. Unduh template terbaru lalu isi tanpa mengubah urutan/nama kolom: '.implode(', ', $expected).'.',
            ]);
        }

        $districts = District::query()->get()->keyBy(fn (District $district) => strtolower($district->name));
        $mitraUsers = User::query()->role('mitra')->where('is_active', true)->get()->keyBy(fn (User $user) => strtolower($user->name));

        $errors = [];
        $parsedRows = [];

        foreach (array_slice($rows, 1) as $index => $row) {
            $rowNumber = $index + 2;
            $cells = array_map(fn ($cell) => trim((string) $cell), $row);
            if (implode('', $cells) === '') {
                continue;
            }

            [$kodeProv, $kodeKab, $kecamatan, $kelurahan, $kodeNks, $sls, $ppl, $noUrutRuta] = array_pad($cells, 8, '');

            if ($kodeProv !== '' && $kodeProv !== '31') {
                $errors[] = "Baris {$rowNumber}: Kode Prov harus 31.";
            }
            if ($kodeKab !== '' && $kodeKab !== '01') {
                $errors[] = "Baris {$rowNumber}: Kode Kab harus 01.";
            }

            $district = $kecamatan !== '' ? $districts->get(strtolower($kecamatan)) : null;
            if ($kecamatan === '' || ! $district) {
                $errors[] = "Baris {$rowNumber}: Kecamatan '{$kecamatan}' tidak ditemukan.";
            }

            $village = null;
            if ($district) {
                $village = $district->villages()->whereRaw('LOWER(name) = ?', [strtolower($kelurahan)])->first();
            }
            if ($kelurahan === '' || ! $village) {
                $errors[] = "Baris {$rowNumber}: Kelurahan '{$kelurahan}' tidak ditemukan di kecamatan '{$kecamatan}'.";
            }

            if ($kodeNks === '') {
                $errors[] = "Baris {$rowNumber}: Kode NKS wajib diisi.";
            }
            if ($sls === '') {
                $errors[] = "Baris {$rowNumber}: SLS wajib diisi.";
            }

            $mitra = $ppl !== '' ? $mitraUsers->get(strtolower($ppl)) : null;
            if ($ppl === '' || ! $mitra) {
                $errors[] = "Baris {$rowNumber}: PPL/mitra '{$ppl}' tidak ditemukan atau tidak aktif.";
            }

            if ($noUrutRuta === '') {
                $errors[] = "Baris {$rowNumber}: No Urut Ruta wajib diisi.";
            }

            if ($district && $village && $mitra && $kodeNks !== '' && $sls !== '' && $noUrutRuta !== '') {
                $parsedRows[] = [
                    'district_id' => $district->id,
                    'village_id' => $village->id,
                    'kode_nks' => $kodeNks,
                    'sls' => $sls,
                    'ppl' => $mitra->name,
                    'mitra_id' => $mitra->id,
                    'no_urut_ruta' => $noUrutRuta,
                ];
            }
        }

        if ($parsedRows === []) {
            $errors[] = 'Tidak ada baris data yang bisa diproses.';
        }

        if ($errors !== []) {
            return back()->withErrors([
                'file' => 'Template tidak sesuai, upload dibatalkan. '.implode(' ', array_slice($errors, 0, 10))
                    .(count($errors) > 10 ? ' (dan '.(count($errors) - 10).' error lainnya)' : ''),
            ]);
        }

        DB::transaction(function () use ($survey, $mode, $parsedRows): void {
            if ($mode === 'replace') {
                $survey->assignments()->delete();
            }

            $grouped = collect($parsedRows)->groupBy('mitra_id');

            foreach ($grouped as $mitraId => $mitraRows) {
                $assignment = SurveyAssignment::query()->where('survey_id', $survey->id)->where('mitra_id', $mitraId)->first();
                $target = $mode === 'append' && $assignment ? $assignment->target + $mitraRows->count() : $mitraRows->count();

                $assignment = SurveyAssignment::query()->updateOrCreate(
                    ['survey_id' => $survey->id, 'mitra_id' => $mitraId],
                    ['target' => $target]
                );

                foreach ($mitraRows as $row) {
                    SurveyEntry::query()->create([
                        'survey_id' => $survey->id,
                        'survey_assignment_id' => $assignment->id,
                        'district_id' => $row['district_id'],
                        'village_id' => $row['village_id'],
                        'kode_nks' => $row['kode_nks'],
                        'sls' => $row['sls'],
                        'ppl' => $row['ppl'],
                        'no_urut_ruta' => $row['no_urut_ruta'],
                        'entry_status' => 'draft',
                    ]);
                }
            }
        });

        $this->recalculateTarget($survey);

        $mitraCount = collect($parsedRows)->pluck('mitra_id')->unique()->count();
        $message = $mode === 'replace'
            ? "Import alokasi selesai. Alokasi lama diganti. {$mitraCount} mitra dialokasikan, ".count($parsedRows).' baris Ruta dibuat.'
            : "Import alokasi selesai. {$mitraCount} mitra diperbarui, ".count($parsedRows).' baris Ruta ditambahkan.';

        return back()->with('status', $message);
    }

    public function checkpoints(Request $request, Survey $survey): View
    {
        $this->authorizeSurveyAccess($request, $survey);

        return view('panel.pegawai.survey-checkpoints', [
            'survey' => $survey->load(['checkpoints', 'assignments']),
        ]);
    }

    public function storeCheckpoint(Request $request, Survey $survey): RedirectResponse
    {
        $this->authorizeSurveyAccess($request, $survey);
        $this->ensureSurveyIsEditable($survey);

        $data = $request->validate([
            'checkpoint_date' => ['required', 'date', 'after_or_equal:'.$survey->start_date->toDateString(), 'before_or_equal:'.$survey->end_date->toDateString()],
            'target_percentage' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $survey->checkpoints()->create($data);

        return back()->with('status', 'Checkpoint ditambahkan.');
    }

    public function updateCheckpoint(Request $request, Survey $survey, SurveyCheckpoint $checkpoint): RedirectResponse
    {
        $this->authorizeSurveyAccess($request, $survey);
        $this->ensureSurveyIsEditable($survey);
        abort_unless($checkpoint->survey_id === $survey->id, 404);

        $data = $request->validate([
            'checkpoint_date' => ['required', 'date', 'after_or_equal:'.$survey->start_date->toDateString(), 'before_or_equal:'.$survey->end_date->toDateString()],
            'target_percentage' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $checkpoint->update($data);

        return back()->with('status', 'Checkpoint diperbarui.');
    }

    public function deleteCheckpoint(Request $request, Survey $survey, SurveyCheckpoint $checkpoint): RedirectResponse
    {
        $this->authorizeSurveyAccess($request, $survey);
        $this->ensureSurveyIsEditable($survey);
        abort_unless($checkpoint->survey_id === $survey->id, 404);

        $checkpoint->delete();

        return back()->with('status', 'Checkpoint dihapus.');
    }

    public function finishSetup(Request $request, Survey $survey): RedirectResponse
    {
        $this->authorizeSurveyAccess($request, $survey);
        $this->ensureSurveyIsEditable($survey);

        if ($survey->assignments()->count() === 0) {
            return back()->withErrors(['mitra' => 'Tambahkan minimal satu alokasi mitra sebelum menyimpan setup survei.']);
        }

        $this->recalculateTarget($survey);
        $survey->update(['status' => 'Berjalan']);

        return redirect('/pegawai/surveys')->with('status', 'Survei berhasil dibuat.');
    }

    public function setStatus(Request $request, Survey $survey): RedirectResponse
    {
        $this->authorizeSurveyAccess($request, $survey);
        $this->ensureSurveyIsEditable($survey);

        $data = $request->validate([
            'status' => ['required', 'in:Draft,Berjalan,Selesai'],
        ]);

        if (in_array($data['status'], ['Berjalan', 'Selesai'], true) && $survey->assignments()->count() === 0) {
            return back()->withErrors(['mitra' => 'Tambahkan alokasi mitra sebelum menjalankan survei.']);
        }

        $this->recalculateTarget($survey);
        $survey->update(['status' => $data['status']]);

        $message = match ($data['status']) {
            'Draft' => 'Survei dikembalikan ke draft.',
            'Selesai' => 'Survei ditandai selesai.',
            default => 'Survei dijalankan.',
        };

        return redirect('/pegawai/surveys/'.$survey->id)->with('status', $message);
    }

    public function mitraList(Request $request): View
    {
        $teamIds = $this->teamIds($request);
        $surveyIds = Survey::query()->whereIn('team_id', $teamIds)->pluck('id');
        $search = $request->string('search')->value();

        $mitraStats = User::query()
            ->role('mitra')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->with(['assignments' => fn ($q) => $q->whereIn('survey_id', $surveyIds)->with(['survey', 'entries'])])
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString()
            ->through(function (User $mitra): User {
                $mitra->performance = $this->mitraPerformance($mitra);

                return $mitra;
            });

        return view('panel.pegawai.mitra', compact('mitraStats', 'search'));
    }

    public function mitraProfile(Request $request, User $user): View
    {
        $teamIds = $this->teamIds($request);
        $surveyIds = Survey::query()->whereIn('team_id', $teamIds)->pluck('id');
        $user->load(['assignments' => fn ($query) => $query->whereIn('survey_id', $surveyIds)->with(['survey', 'entries'])]);
        $user->performance = $this->mitraPerformance($user);

        return view('panel.pegawai.mitra-profile', ['mitra' => $user]);
    }

    public function entries(Request $request): View
    {
        $teamIds = $this->teamIds($request);
        $surveyIds = Survey::query()->whereIn('team_id', $teamIds)->pluck('id');
        $selectedSurveyId = $request->integer('survey_id') ?: null;
        $filters = $request->only(['modified_from', 'modified_to']);
        $statusFilter = $request->string('status')->value();
        $pplSearch = $request->string('ppl_search')->value();

        $sortable = [
            'submit' => 'survey_entries.created_at',
            'modified' => 'survey_entries.updated_at',
            'kecamatan' => 'districts.name',
            'kelurahan' => 'villages.name',
            'kode_nks' => 'survey_entries.kode_nks',
            'sls' => 'survey_entries.sls',
            'ppl' => 'survey_entries.ppl',
            'no_urut_ruta' => 'survey_entries.no_urut_ruta',
            'status' => 'survey_entries.is_valid',
        ];
        $sortKey = $request->string('sort', 'submit')->value();
        $sortDir = $request->string('direction', 'desc')->value() === 'asc' ? 'asc' : 'desc';

        $selectedSurvey = $selectedSurveyId
            ? Survey::query()->whereIn('id', $surveyIds)->with('variables')->find($selectedSurveyId)
            : null;

        // Kolom variabel (dinamis per survei) juga bisa dipakai untuk sort, memakai
        // key "var_<id>" agar tidak bentrok dengan kolom identitas di atas.
        $sortVariable = null;
        if ($selectedSurvey && str_starts_with($sortKey, 'var_')) {
            $sortVariable = $selectedSurvey->variables->firstWhere('id', (int) substr($sortKey, 4));
        }
        if (! $sortVariable && ! array_key_exists($sortKey, $sortable)) {
            $sortKey = 'submit';
        }

        $entryCounts = null;
        $entries = null;

        if ($selectedSurvey) {
            $baseQuery = $this->applyEntryTimeFilters(SurveyEntry::query()
                ->select('survey_entries.*')
                ->selectSub(
                    SurveyEntry::query()
                        ->from('survey_entries as survey_entry_sequence')
                        ->selectRaw('count(*)')
                        ->whereColumn('survey_entry_sequence.survey_id', 'survey_entries.survey_id')
                        ->where('survey_entry_sequence.entry_status', 'submitted')
                        ->where(function ($query): void {
                            $query
                                ->whereColumn('survey_entry_sequence.created_at', '<', 'survey_entries.created_at')
                                ->orWhere(function ($sameTimeQuery): void {
                                    $sameTimeQuery
                                        ->whereColumn('survey_entry_sequence.created_at', 'survey_entries.created_at')
                                        ->whereColumn('survey_entry_sequence.id', '<=', 'survey_entries.id');
                                });
                        }),
                    'survey_entry_number'
                )
                ->leftJoin('districts', 'districts.id', '=', 'survey_entries.district_id')
                ->leftJoin('villages', 'villages.id', '=', 'survey_entries.village_id')
                ->when($sortVariable, fn ($query) => $query->leftJoin('entry_variable_values as sort_value', function ($join) use ($sortVariable): void {
                    $join->on('sort_value.survey_entry_id', '=', 'survey_entries.id')
                        ->where('sort_value.survey_variable_id', '=', $sortVariable->id);
                }))
                ->with(['survey.variables', 'assignment.mitra', 'validator', 'values.variable', 'district', 'village'])
                ->where('survey_entries.survey_id', $selectedSurvey->id)
                ->where('survey_entries.entry_status', 'submitted')
                ->when($pplSearch !== '', fn ($query) => $query->where('survey_entries.ppl', 'like', '%'.$pplSearch.'%')), $request);

            $entryCounts = [
                'total' => (clone $baseQuery)->count(),
                'invalid' => (clone $baseQuery)->where('is_valid', false)->count(),
            ];

            $sortColumn = $sortVariable
                ? ($sortVariable->data_type === 'number' ? DB::raw('CAST(sort_value.value AS DECIMAL(18,4))') : 'sort_value.value')
                : $sortable[$sortKey];

            $entries = (clone $baseQuery)
                ->when($statusFilter === 'invalid', fn ($query) => $query->where('is_valid', false))
                ->orderBy($sortColumn, $sortDir)
                ->orderBy('survey_entries.id', $sortDir)
                ->paginate(20)
                ->withQueryString();
        }

        return view('panel.pegawai.entries', [
            'surveys' => Survey::query()->whereIn('id', $surveyIds)->orderByDesc('created_at')->get(),
            'selectedSurveyId' => $selectedSurveyId,
            'selectedSurvey' => $selectedSurvey,
            // Entri hanya dimuat setelah sebuah survei dipilih (variabel validasi berbeda tiap survei)
            'entries' => $entries,
            'entryCounts' => $entryCounts,
            'statusFilter' => $statusFilter,
            'filters' => $filters,
            'pplSearch' => $pplSearch,
            'sortKey' => $sortKey,
            'sortDir' => $sortDir,
        ]);
    }

    public function updates(Request $request): View
    {
        $teamIds = $this->teamIds($request);
        $surveyIds = Survey::query()->whereIn('team_id', $teamIds)->pluck('id');

        $query = SurveyEntry::query()
            ->with(['survey', 'assignment.mitra', 'district', 'village'])
            ->whereIn('survey_id', $surveyIds)
            ->where('entry_status', 'submitted')
            ->when($request->filled('mitra_id'), fn ($q) => $q->whereHas('assignment', fn ($assignmentQuery) => $assignmentQuery->where('mitra_id', $request->integer('mitra_id'))))
            ->when($request->filled('district_id'), fn ($q) => $q->where('district_id', $request->integer('district_id')))
            ->when($request->filled('modified_from'), fn ($q) => $q->where('updated_at', '>=', $request->date('modified_from')?->startOfDay()))
            ->when($request->filled('modified_to'), fn ($q) => $q->where('updated_at', '<=', $request->date('modified_to')?->endOfDay()));

        return view('panel.pegawai.updates', [
            'entries' => $query->latest()->paginate(20)->withQueryString(),
            'mitraUsers' => User::query()->role('mitra')->orderBy('name')->get(),
            'districts' => District::query()->orderBy('name')->get(),
            'filters' => $request->only(['mitra_id', 'district_id', 'modified_from', 'modified_to']),
        ]);
    }

    public function updateDetail(Request $request, SurveyEntry $entry): View
    {
        abort_unless(in_array($entry->survey->team_id, $this->teamIds($request)->all(), true), 403);

        return view('panel.pegawai.update-detail', [
            'entry' => $entry->load(['survey.variables', 'assignment.mitra', 'values.variable', 'district', 'village']),
        ]);
    }

    public function exportEntries(Request $request)
    {
        $teamIds = $this->teamIds($request);
        $surveyIds = Survey::query()->whereIn('team_id', $teamIds)->pluck('id');
        $selectedSurveyId = $request->integer('survey_id') ?: null;
        $format = $request->string('format', 'xlsx')->value();

        $entries = $this->applyEntryTimeFilters(SurveyEntry::query()
            ->select('survey_entries.*')
            ->selectSub(
                SurveyEntry::query()
                    ->from('survey_entries as survey_entry_sequence')
                    ->selectRaw('count(*)')
                    ->whereColumn('survey_entry_sequence.survey_id', 'survey_entries.survey_id')
                    ->where('survey_entry_sequence.entry_status', 'submitted')
                    ->where(function ($query): void {
                        $query
                            ->whereColumn('survey_entry_sequence.created_at', '<', 'survey_entries.created_at')
                            ->orWhere(function ($sameTimeQuery): void {
                                $sameTimeQuery
                                    ->whereColumn('survey_entry_sequence.created_at', 'survey_entries.created_at')
                                    ->whereColumn('survey_entry_sequence.id', '<=', 'survey_entries.id');
                            });
                    }),
                'survey_entry_number'
            )
            ->with(['survey', 'assignment.mitra', 'district', 'village', 'values.variable'])
            ->whereIn('survey_id', $surveyIds)
            ->where('entry_status', 'submitted')
            ->when($selectedSurveyId, fn ($query) => $query->where('survey_id', $selectedSurveyId)), $request)
            ->latest()
            ->get();

        $variableNames = $entries->flatMap(fn (SurveyEntry $entry) => $entry->values->map(fn ($value) => $value->variable->name))->unique()->values()->all();
        $headings = ['Kode Prov', 'Kode Kab', 'Kecamatan', 'Kelurahan', 'Kode NKS', 'SLS', 'PPL', 'No Urut Ruta', ...$variableNames, 'Status', 'Submit', 'Modified'];

        $rows = $entries->map(function (SurveyEntry $entry) use ($variableNames): array {
            $valueMap = $entry->values->mapWithKeys(fn ($value) => [$value->variable->name => $value->value]);
            $variableValues = collect($variableNames)->mapWithKeys(fn (string $name) => [$name => $valueMap[$name] ?? '-'])->all();

            return [
                'Kode Prov' => '31',
                'Kode Kab' => '01',
                'Kecamatan' => $entry->district?->name ?? '-',
                'Kelurahan' => $entry->village?->name ?? '-',
                'Kode NKS' => $entry->kode_nks ?? '-',
                'SLS' => $entry->sls ?? '-',
                'PPL' => $entry->ppl ?? $entry->assignment->mitra->name,
                'No Urut Ruta' => $entry->no_urut_ruta ?? '-',
            ] + $variableValues + [
                'Status' => $entry->is_valid === null ? 'Menunggu' : ($entry->is_valid ? 'Valid' : 'Tidak Valid'),
                'Submit' => $entry->created_at->format('d/m/Y H:i'),
                'Modified' => $entry->updated_at->format('d/m/Y H:i'),
            ];
        });

        if ($format === 'csv') {
            return $this->downloadCsv($rows, 'data-entri-pegawai.csv');
        }

        return Excel::download(new EntriesExport($rows, $headings), 'data-entri-pegawai.xlsx');
    }

    public function notifications(Request $request): View
    {
        return view('panel.pegawai.notifications', [
            'notifications' => $request->user()->notifications()->latest()->paginate(20),
        ]);
    }

    private function teamIds(Request $request): Collection
    {
        return $request->user()->teams()->pluck('teams.id');
    }

    private function applyEntryTimeFilters(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('modified_from'), fn (Builder $query): Builder => $query->where('survey_entries.updated_at', '>=', $request->date('modified_from')?->startOfDay()))
            ->when($request->filled('modified_to'), fn (Builder $query): Builder => $query->where('survey_entries.updated_at', '<=', $request->date('modified_to')?->endOfDay()));
    }

    private function authorizeSurveyAccess(Request $request, Survey $survey): void
    {
        abort_unless($request->user()->teams()->whereKey($survey->team_id)->exists(), 403);
    }

    private function ensureSurveyIsEditable(Survey $survey): void
    {
        abort_if($survey->status === 'Selesai', 403, 'Survei selesai tidak dapat diedit.');
    }

    private function syncSurveyStatuses(Collection $teamIds): void
    {
        Survey::query()->whereIn('team_id', $teamIds)->with('assignments')->get()->each(function (Survey $survey): void {
            $this->syncSurveyStatus($survey);
        });
    }

    private function syncSurveyStatus(Survey $survey): void
    {
        // Draft dan selesai manual tidak diutak-atik otomatis.
        if (in_array($survey->status, ['Draft', 'Selesai'], true)) {
            return;
        }

        $target = (int) $survey->total_target;
        if ($target <= 0) {
            return;
        }

        $progress = (int) $survey->assignments()->sum('current_progress');
        $status = $progress >= $target ? 'Selesai' : 'Berjalan';

        if ($survey->status !== $status) {
            $survey->update(['status' => $status]);
        }
    }

    /**
     * Total target survei = akumulasi target seluruh mitra yang dialokasikan.
     */
    private function recalculateTarget(Survey $survey): void
    {
        $total = (int) $survey->assignments()->sum('target');

        if ((int) $survey->total_target !== $total) {
            $survey->update(['total_target' => $total]);
        }
    }

    /**
     * @return array{
     *     total_assignments:int,
     *     on_time:int,
     *     late:int,
     *     timeliness_score:float,
     *     total_entries:int
     * }
     */
    private function mitraPerformance(User $mitra): array
    {
        $assignments = $mitra->assignments;
        $totalAssignments = $assignments->count();

        $lateCount = 0;
        foreach ($assignments as $assignment) {
            $assignment->is_late = $this->isAssignmentLate($assignment);

            if ($assignment->is_late) {
                $lateCount++;
            }
        }

        $onTimeCount = max(0, $totalAssignments - $lateCount);
        $timelinessScore = $totalAssignments > 0 ? ($onTimeCount / $totalAssignments) * 100 : 0;
        $totalEntries = $assignments->flatMap(fn (SurveyAssignment $assignment) => $assignment->entries->where('entry_status', 'submitted'))->count();

        return [
            'total_assignments' => $totalAssignments,
            'on_time' => $onTimeCount,
            'late' => $lateCount,
            'timeliness_score' => round($timelinessScore, 1),
            'total_entries' => $totalEntries,
        ];
    }

    /**
     * Terlambat jika penyelesaian target (entri ke-N) jatuh setelah tanggal akhir survei,
     * atau survei sudah lewat tanggal akhir namun target belum tercapai.
     */
    private function isAssignmentLate(SurveyAssignment $assignment): bool
    {
        $entries = $assignment->entries->where('entry_status', 'submitted')->sortBy('created_at')->values();
        $completionEntry = $assignment->target > 0 && $entries->count() >= $assignment->target
            ? $entries->get($assignment->target - 1)
            : null;

        return $completionEntry
            ? $completionEntry->created_at->toDateString() > $assignment->survey->end_date->toDateString()
            : ($assignment->survey->end_date->isPast() && $assignment->current_progress < $assignment->target);
    }

    private function downloadCsv(Collection $rows, string $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        return Response::stream(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            $columns = $rows->flatMap(fn ($row) => array_keys($row))->unique()->values()->all();
            fputcsv($handle, $columns);

            foreach ($rows as $row) {
                fputcsv($handle, collect($columns)->map(fn ($column) => $row[$column] ?? '')->all());
            }

            fclose($handle);
        }, 200, $headers);
    }
}
