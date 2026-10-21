<?php

namespace App\Http\Controllers\Web\Admin;

use App\Exports\AssignmentTemplateExport;
use App\Exports\PegawaiTemplateExport;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SlsArea;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyCheckpoint;
use App\Models\SurveyEntry;
use App\Models\SurveyVariable;
use App\Models\User;
use App\Models\Village;
use App\Notifications\MitraAssignedNotification;
use App\Services\FasihProgressImporter;
use App\Services\MitraCheckpointProgress;
use App\Services\PapiAllocationImporter;
use App\Services\SurveyPegawaiAssigner;
use App\Services\SurveyProgressSummary;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Pengelolaan survei oleh admin. Survei PAPI disiapkan lewat variabel, alokasi, dan checkpoint;
 * survei CAPI cukup dibuat dengan data scraping FASIH pertama.
 */
class SurveyManagementController extends Controller
{
    public function index(SurveyProgressSummary $progressSummary): View
    {
        return view('panel.survey-index', [
            'surveys' => $progressSummary->attach(Survey::query()->withCount('assignments')->latest()->get()),
            'panelTitle' => 'Admin',
            'menuView' => 'panel.admin.menu',
            'base' => '/admin',
            'canManage' => true,
        ]);
    }

    public function create(): View
    {
        return view('panel.admin.surveys.create', [
            'pegawaiOptions' => $this->activePegawai()->get(['id', 'name', 'email']),
        ]);
    }

    public function store(Request $request, FasihProgressImporter $importer, SurveyPegawaiAssigner $pegawaiAssigner): RedirectResponse
    {
        $isCapi = $request->input('type') === Survey::TYPE_CAPI;

        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(Survey::TYPES))],
            'title' => ['required', 'string', 'max:255', Rule::unique('surveys', 'title')],
            'description' => ['nullable', 'string'],
            'start_date' => $isCapi ? ['required', 'date'] : ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'file' => $isCapi ? ['required', 'file', 'mimes:csv,txt', 'max:10240'] : ['exclude'],
            'checkpoints' => $isCapi ? ['nullable', 'array', 'max:10'] : ['exclude'],
            'checkpoints.*.date' => $isCapi ? ['required', 'date', 'distinct', 'after_or_equal:start_date', 'before_or_equal:end_date'] : ['exclude'],
            'checkpoints.*.target' => $isCapi ? ['required', 'integer', 'min:1', 'max:100'] : ['exclude'],
            'pegawai_ids' => ['nullable', 'array'],
            'pegawai_ids.*' => ['integer', 'distinct', Rule::in($this->activePegawai()->pluck('id')->all())],
            'pegawai_file' => ['nullable', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
        ], [
            'title.unique' => 'Nama survei/sensus sudah dipakai. Gunakan nama lain.',
            'pegawai_ids.*.in' => 'Pegawai yang dipilih tidak ditemukan atau tidak aktif.',
            'pegawai_file.mimes' => 'File pegawai harus berformat Excel (.xlsx) atau CSV.',
            'file.required' => 'Survei CAPI memerlukan file data scraping FASIH pertama.',
            'file.mimes' => 'File data scraping harus berformat CSV.',
            'checkpoints.max' => 'Maksimal 10 checkpoint.',
            'checkpoints.*.date.required' => 'Lengkapi tanggal setiap checkpoint.',
            'checkpoints.*.date.distinct' => 'Setiap checkpoint harus memakai tanggal yang berbeda.',
            'checkpoints.*.date.after_or_equal' => 'Tanggal checkpoint harus di dalam periode survei.',
            'checkpoints.*.date.before_or_equal' => 'Tanggal checkpoint harus di dalam periode survei.',
            'checkpoints.*.target.required' => 'Lengkapi target setiap checkpoint.',
            'checkpoints.*.target.min' => 'Target checkpoint minimal 1%.',
            'checkpoints.*.target.max' => 'Target checkpoint maksimal 100%.',
        ]);

        // Pegawai dari centang dan dari file template digabung; file yang salah membatalkan pembuatan survei.
        $pegawai = User::query()->whereKey($data['pegawai_ids'] ?? [])->get();
        if ($request->hasFile('pegawai_file')) {
            $pegawai = $pegawai->merge($pegawaiAssigner->parse($request->file('pegawai_file'), 'pegawai_file'));
        }

        $checkpoints = $this->sortNewCheckpoints($data['checkpoints'] ?? []);

        $survey = DB::transaction(function () use ($request, $data, $isCapi, $checkpoints, $importer): Survey {
            // Total target PAPI dihitung dari alokasi mitra; CAPI dari total beban pada data FASIH.
            $survey = Survey::query()->create([
                'type' => $data['type'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'created_by' => $request->user()->id,
                'status' => $isCapi ? 'Berjalan' : 'Draft',
                'total_target' => 0,
            ]);

            if ($isCapi) {
                foreach ($checkpoints as $checkpoint) {
                    $survey->checkpoints()->create([
                        'checkpoint_date' => $checkpoint['date'],
                        'target_percentage' => $checkpoint['target'],
                    ]);
                }

                $importer->import($request->file('file'), $request->user(), $survey);
            }

            return $survey;
        });

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => 'admin.survey.create',
            'description' => 'Membuat survei '.$survey->typeLabel().' '.$survey->title,
        ]);

        $pegawaiAssigner->assign($survey, $pegawai, $request->user());

        return $isCapi
            ? redirect('/admin/monitoring/progres?survey='.$survey->id)->with('status', 'Survei CAPI dibuat dan data FASIH pertama berhasil diimpor.'
                .(count($checkpoints) > 0 ? ' '.count($checkpoints).' checkpoint ditambahkan.' : '')
                .($importer->skippedRows() > 0 ? " {$importer->skippedRows()} baris tanpa kecamatan terdaftar dilewati." : ''))
            : redirect('/admin/surveys/'.$survey->id.'/variables');
    }

    /**
     * Checkpoint yang diisi saat membuat survei CAPI diurutkan per tanggal; seperti di halaman checkpoint,
     * targetnya harus terus naik.
     *
     * @param  array<int, array{date: string, target: int|string}>  $checkpoints
     * @return list<array{date: string, target: int}>
     *
     * @throws ValidationException
     */
    private function sortNewCheckpoints(array $checkpoints): array
    {
        $sorted = collect($checkpoints)
            ->map(fn (array $checkpoint): array => ['date' => Carbon::parse($checkpoint['date'])->toDateString(), 'target' => (int) $checkpoint['target']])
            ->sortBy('date')
            ->values();

        foreach ($sorted as $index => $checkpoint) {
            $previous = $sorted->get($index - 1);

            if ($previous && $checkpoint['target'] <= $previous['target']) {
                throw ValidationException::withMessages([
                    'checkpoints' => 'Target checkpoint harus naik mengikuti tanggal: checkpoint '.($index + 1).' harus lebih dari '.$previous['target'].'%.',
                ]);
            }
        }

        return $sorted->all();
    }

    public function show(Survey $survey): View
    {
        return view('panel.survey-show', [
            'survey' => $survey->loadDetail(),
            'panelTitle' => 'Admin',
            'menuView' => 'panel.admin.menu',
            'base' => '/admin',
            'canManage' => true,
        ]);
    }

    public function edit(Request $request, Survey $survey): View
    {
        // Header setup menghitung variabel & alokasi sendiri; halaman ini hanya butuh riwayat import FASIH.
        $survey->load(['fasihImports' => fn ($query) => $query->with('user')->latest('id')]);

        return view('panel.admin.surveys.edit', ['survey' => $survey]);
    }

    public function update(Request $request, Survey $survey): RedirectResponse
    {
        $this->ensureSurveyIsEditable($survey);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255', Rule::unique('surveys', 'title')->ignore($survey)],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ], [
            'title.unique' => 'Nama survei/sensus sudah dipakai. Gunakan nama lain.',
        ]);

        $survey->update($data);

        ActivityLog::query()->create(['user_id' => $request->user()->id, 'action' => 'admin.survey.update', 'description' => 'Edit survei '.$survey->title]);

        return back()->with('status', 'Survei berhasil diperbarui.');
    }

    public function destroy(Request $request, Survey $survey): RedirectResponse
    {
        $this->ensureSurveyIsEditable($survey);

        $title = $survey->title;
        $survey->delete();

        ActivityLog::query()->create(['user_id' => $request->user()->id, 'action' => 'admin.survey.delete', 'description' => 'Menghapus survei '.$title]);

        return redirect('/admin/surveys')->with('status', 'Survei berhasil dihapus.');
    }

    public function variables(Request $request, Survey $survey): View|RedirectResponse
    {
        $this->ensurePapiSurvey($survey);

        if ($survey->status === 'Selesai') {
            return redirect('/admin/surveys/'.$survey->id)->withErrors(['survey' => 'Survei selesai tidak dapat diedit.']);
        }

        return view('panel.admin.surveys.variables', [
            'survey' => $survey->load('variables'),
        ]);
    }

    public function storeVariable(Request $request, Survey $survey): RedirectResponse
    {
        $this->ensurePapiSurvey($survey);
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

        return back()->with('status', 'Variabel isian disimpan.');
    }

    public function downloadVariableTemplate(Request $request, Survey $survey)
    {
        $this->ensurePapiSurvey($survey);

        $variables = $survey->variables()->orderBy('id')->get();
        $baseColumns = ['ID', 'Mitra', 'Responden', 'Wilayah'];

        $rows = [
            array_merge(['', '', '', 'Progress Pencacahan '.$survey->title], array_fill(0, max(0, $variables->count() + 1), '')),
            array_merge($baseColumns, $variables->pluck('name')->all(), ['Status']),
            array_merge(['1', 'Nama Mitra', 'Nama Responden', 'Kecamatan / Kelurahan'], $variables->pluck('example_format')->map(fn ($example) => $example ?: '-')->all(), ['Selesai']),
        ];

        return Excel::download(new class($rows) implements FromArray
        {
            public function __construct(private readonly array $rows) {}

            public function array(): array
            {
                return $this->rows;
            }
        }, 'template-'.$survey->title.'.xlsx');
    }

    public function updateVariable(Request $request, Survey $survey, SurveyVariable $variable): RedirectResponse
    {
        $this->ensurePapiSurvey($survey);
        $this->ensureSurveyIsEditable($survey);
        abort_unless($variable->survey_id === $survey->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'data_type' => ['required', 'in:text,number'],
            'example_format' => ['nullable', 'string', 'max:255'],
        ]);

        $variable->update($data);

        return back()->with('status', 'Variabel isian diperbarui.');
    }

    public function deleteVariable(Request $request, Survey $survey, SurveyVariable $variable): RedirectResponse
    {
        $this->ensurePapiSurvey($survey);
        $this->ensureSurveyIsEditable($survey);
        abort_unless($variable->survey_id === $survey->id, 404);

        $variable->delete();

        return back()->with('status', 'Variabel isian dihapus.');
    }

    public function assignments(Request $request, Survey $survey): View|RedirectResponse
    {
        $this->ensurePapiSurvey($survey);

        if ($survey->status === 'Selesai') {
            return redirect('/admin/surveys/'.$survey->id)->withErrors(['survey' => 'Survei selesai tidak dapat diedit.']);
        }

        return view('panel.admin.surveys.assignments', [
            'survey' => $survey->load('assignments.mitra'),
            'mitraUsers' => User::query()->role('mitra')->where('is_active', true)->orderBy('name')->get(),
            'villages' => Village::query()->with('district')->orderBy('name')->get(),
            'slsByVillage' => SlsArea::query()->orderBy('name')->get(['village_id', 'name'])
                ->groupBy('village_id')
                ->map(fn ($areas) => $areas->pluck('name')->unique()->values()),
        ]);
    }

    /**
     * Tambah manual mengikuti kolom impor Excel: mitra, kelurahan, SLS, PPL, dan jumlah ruta.
     * Sejumlah "target ruta" entri Open dibuat dengan nomor urut ruta berurutan, melanjutkan
     * nomor terakhir pada kelurahan + SLS itu (maksimal 99).
     */
    public function storeAssignment(Request $request, Survey $survey): RedirectResponse
    {
        $this->ensurePapiSurvey($survey);
        $this->ensureSurveyIsEditable($survey);

        $data = $request->validate([
            'mitra_id' => ['required', Rule::exists('users', 'id')],
            'village_id' => ['required', Rule::exists('villages', 'id')],
            'sls' => ['required', 'string', 'max:160'],
            'ppl' => ['nullable', 'string', 'max:255'],
            'target' => ['required', 'integer', 'min:1', 'max:99'],
        ], [
            'target.max' => 'Target ruta maksimal 99 karena nomor urut ruta hanya 2 digit.',
        ], [
            'mitra_id' => 'mitra',
            'village_id' => 'kelurahan',
            'sls' => 'SLS',
            'ppl' => 'PPL',
            'target' => 'target ruta',
        ]);

        $mitra = User::query()->findOrFail($data['mitra_id']);
        $village = Village::query()->findOrFail($data['village_id']);
        $sls = trim(preg_replace('/\s+/', ' ', $data['sls']));
        $rutaCount = (int) $data['target'];

        $lastNumber = (int) $survey->entries()
            ->where('village_id', $village->id)
            ->get(['sls', 'no_urut_ruta'])
            ->filter(fn (SurveyEntry $entry): bool => strtolower(trim((string) $entry->sls)) === strtolower($sls))
            ->max(fn (SurveyEntry $entry): int => (int) $entry->no_urut_ruta);

        if ($lastNumber + $rutaCount > 99) {
            $remaining = max(0, 99 - $lastNumber);

            throw ValidationException::withMessages([
                'target' => $remaining > 0
                    ? "SLS ini sudah punya ruta sampai nomor {$lastNumber}. Tersisa {$remaining} nomor urut (maksimal 99)."
                    : 'SLS ini sudah punya ruta sampai nomor 99. Pilih SLS lain.',
            ]);
        }

        $assignment = DB::transaction(function () use ($survey, $mitra, $village, $sls, $data, $rutaCount, $lastNumber): SurveyAssignment {
            $assignment = SurveyAssignment::query()->firstOrNew(['survey_id' => $survey->id, 'mitra_id' => $mitra->id]);
            $assignment->target = ($assignment->exists ? (int) $assignment->target : 0) + $rutaCount;
            $assignment->current_progress ??= 0;
            $assignment->save();

            foreach (range($lastNumber + 1, $lastNumber + $rutaCount) as $number) {
                SurveyEntry::query()->create([
                    'survey_id' => $survey->id,
                    'survey_assignment_id' => $assignment->id,
                    'district_id' => $village->district_id,
                    'village_id' => $village->id,
                    'sls' => $sls,
                    'ppl' => filled($data['ppl'] ?? null) ? trim($data['ppl']) : $mitra->name,
                    'no_urut_ruta' => (string) $number,
                    'entry_status' => SurveyEntry::STATUS_OPEN,
                ]);
            }

            return $assignment;
        });

        $survey->recalculateTarget();

        // Survei Draft belum terlihat mitra; notifikasinya dikirim saat admin menjalankan survei.
        if ($assignment->wasRecentlyCreated && $survey->status === 'Berjalan') {
            $assignment->mitra?->notify(new MitraAssignedNotification($survey));
        }

        return back()->with('status', "{$rutaCount} ruta dialokasikan ke {$mitra->name} (no urut ".($lastNumber + 1).'–'.($lastNumber + $rutaCount).').');
    }

    public function deleteAssignment(Request $request, Survey $survey, SurveyAssignment $assignment): RedirectResponse
    {
        $this->ensurePapiSurvey($survey);
        $this->ensureSurveyIsEditable($survey);
        abort_unless($assignment->survey_id === $survey->id, 404);

        // Menghapus alokasi ikut menghapus entrinya, jadi isian mitra tidak boleh ikut hilang.
        $filledCount = $assignment->entries()->where('entry_status', '!=', SurveyEntry::STATUS_OPEN)->count();
        if ($filledCount > 0) {
            return back()->withErrors(['mitra' => "Alokasi {$assignment->mitra?->name} tidak bisa dihapus karena mitra sudah mengisi {$filledCount} ruta (draft atau terkirim)."]);
        }

        $assignment->delete();
        $survey->recalculateTarget();

        return back()->with('status', 'Alokasi mitra dihapus.');
    }

    public function downloadAssignmentTemplate(Survey $survey): BinaryFileResponse
    {
        $this->ensurePapiSurvey($survey);

        $exampleMitra = User::query()->role('mitra')->where('is_active', true)->where('email', 'like', '%@gmail.com')
            ->orderBy('name')->limit(2)->get(['name', 'email'])
            ->map(fn (User $user): array => ['name' => $user->name, 'email' => $user->email]);

        return Excel::download(
            new AssignmentTemplateExport($exampleMitra, Village::query()->orderBy('name')->pluck('name')),
            'template-alokasi-'.Str::slug($survey->title).'.xlsx'
        );
    }

    public function downloadPegawaiTemplate(): BinaryFileResponse
    {
        $examplePegawai = $this->activePegawai()->limit(3)->get(['name', 'email'])
            ->map(fn (User $user): array => ['name' => $user->name, 'email' => $user->email]);

        return Excel::download(new PegawaiTemplateExport($examplePegawai), 'template-pegawai-survei.xlsx');
    }

    public function importAssignments(Request $request, Survey $survey, PapiAllocationImporter $importer): RedirectResponse
    {
        $this->ensurePapiSurvey($survey);
        $this->ensureSurveyIsEditable($survey);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ], [
            'file.required' => 'Pilih file template alokasi yang sudah diisi.',
            'file.mimes' => 'File harus berformat Excel (.xlsx) atau CSV.',
        ]);

        $hadAllocation = $survey->assignments()->exists();
        try {
            $result = $importer->import($survey, $request->file('file'));
        } catch (ValidationException $exception) {
            throw $exception->redirectTo(url()->previous().'#importForm');
        }

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => 'admin.survey.allocation_import',
            'description' => "Mengimpor alokasi {$result['rows']} ruta untuk {$result['mitra']} mitra pada survei {$survey->title}",
        ]);

        $message = ($hadAllocation ? 'Alokasi lama diganti. ' : '')
            ."{$result['rows']} ruta dialokasikan ke {$result['mitra']} mitra."
            .($survey->status === 'Berjalan' ? ' Survei sudah muncul di akun mitra tersebut.' : ' Jalankan survei agar muncul di akun mitra.');

        return back()->withFragment('importForm')->with('status', $message)->with('import_success', $message);
    }

    public function checkpoints(Request $request, Survey $survey, MitraCheckpointProgress $progress): View|RedirectResponse
    {
        if ($survey->status === 'Selesai') {
            return redirect('/admin/surveys/'.$survey->id)->withErrors(['survey' => 'Survei selesai tidak dapat diedit.']);
        }

        return view('panel.admin.surveys.checkpoints', [
            'survey' => $survey->load('checkpoints'),
            'mitraProgress' => $progress->forSurvey($survey),
        ]);
    }

    /**
     * Satu tanggal hanya punya satu checkpoint; menyimpan tanggal yang sama memperbarui targetnya
     * dan membuat peringatannya dikirim ulang bila tanggalnya sudah lewat.
     */
    public function storeCheckpoint(Request $request, Survey $survey): RedirectResponse
    {
        $this->ensureSurveyIsEditable($survey);

        $data = $request->validate([
            'checkpoint_date' => ['required', 'date', 'after_or_equal:'.$survey->start_date->toDateString(), 'before_or_equal:'.$survey->end_date->toDateString()],
            'target_percentage' => ['required', 'integer', 'min:1', 'max:100'],
        ], [
            'checkpoint_date.after_or_equal' => 'Tanggal checkpoint harus di dalam periode survei.',
            'checkpoint_date.before_or_equal' => 'Tanggal checkpoint harus di dalam periode survei.',
            'target_percentage.min' => 'Target minimal 1%.',
            'target_percentage.max' => 'Target maksimal 100%.',
        ], [
            'checkpoint_date' => 'tanggal checkpoint',
            'target_percentage' => 'target capaian',
        ]);

        $this->ensureCheckpointTargetIncreases($survey, $data['checkpoint_date'], (int) $data['target_percentage']);

        $checkpoint = SurveyCheckpoint::query()
            ->where('survey_id', $survey->id)
            ->whereDate('checkpoint_date', $data['checkpoint_date'])
            ->first() ?? new SurveyCheckpoint(['survey_id' => $survey->id]);
        $checkpoint->fill([
            'checkpoint_date' => $data['checkpoint_date'],
            'target_percentage' => $data['target_percentage'],
            'notified_at' => null,
        ])->save();

        return back()->with('status', 'Checkpoint disimpan.');
    }

    /**
     * Checkpoint diurutkan per tanggal dan targetnya harus naik: lebih tinggi dari checkpoint sebelumnya
     * dan lebih rendah dari checkpoint sesudahnya. Checkpoint pada tanggal yang sama sedang diperbarui.
     *
     * @throws ValidationException
     */
    private function ensureCheckpointTargetIncreases(Survey $survey, string $date, int $target): void
    {
        $date = Carbon::parse($date)->toDateString();
        $ordered = $survey->checkpoints()->get()->values();
        $label = fn (SurveyCheckpoint $checkpoint): string => 'checkpoint '.($ordered->search(fn (SurveyCheckpoint $item): bool => $item->is($checkpoint)) + 1)
            .' ('.$checkpoint->checkpoint_date->locale('id')->translatedFormat('d M Y').')';

        $previous = $ordered->last(fn (SurveyCheckpoint $checkpoint): bool => $checkpoint->checkpoint_date->toDateString() < $date);
        $next = $ordered->first(fn (SurveyCheckpoint $checkpoint): bool => $checkpoint->checkpoint_date->toDateString() > $date);

        if ($previous && $target <= $previous->target_percentage) {
            throw ValidationException::withMessages(['target_percentage' => "Target harus lebih dari {$previous->target_percentage}%, target {$label($previous)}."]);
        }
        if ($next && $target >= $next->target_percentage) {
            throw ValidationException::withMessages(['target_percentage' => "Target harus kurang dari {$next->target_percentage}%, target {$label($next)}."]);
        }
    }

    public function deleteCheckpoint(Request $request, Survey $survey, SurveyCheckpoint $checkpoint): RedirectResponse
    {
        $this->ensureSurveyIsEditable($survey);
        abort_unless($checkpoint->survey_id === $survey->id, 404);

        $checkpoint->delete();

        return back()->with('status', 'Checkpoint dihapus.');
    }

    public function finishSetup(Request $request, Survey $survey): RedirectResponse
    {
        $this->ensurePapiSurvey($survey);
        $this->ensureSurveyIsEditable($survey);

        if ($survey->assignments()->count() === 0) {
            return back()->withErrors(['mitra' => 'Tambahkan minimal satu alokasi mitra sebelum menyimpan setup survei.']);
        }

        $survey->recalculateTarget();
        $wasDraft = $survey->status === 'Draft';
        $survey->update(['status' => 'Berjalan']);

        if ($wasDraft) {
            $survey->assignments()->with('mitra')->get()
                ->each(fn (SurveyAssignment $assignment) => $assignment->mitra?->notify(new MitraAssignedNotification($survey)));
        }

        return redirect('/admin/surveys')->with('status', 'Survei berhasil dibuat.');
    }

    public function setStatus(Request $request, Survey $survey): RedirectResponse
    {
        $this->ensureSurveyIsEditable($survey);

        $data = $request->validate([
            'status' => ['required', 'in:Draft,Berjalan,Selesai'],
        ]);

        if (! $survey->isCapi() && in_array($data['status'], ['Berjalan', 'Selesai'], true) && $survey->assignments()->count() === 0) {
            return back()->withErrors(['mitra' => 'Tambahkan alokasi mitra sebelum menjalankan survei.']);
        }

        $survey->recalculateTarget();
        $survey->update(['status' => $data['status']]);

        $message = match ($data['status']) {
            'Draft' => 'Survei dikembalikan ke draft.',
            'Selesai' => 'Survei ditandai selesai.',
            default => 'Survei dijalankan.',
        };

        return redirect('/admin/surveys/'.$survey->id)->with('status', $message);
    }

    /**
     * Target mitra tidak boleh lebih kecil dari jumlah ruta yang sudah ia pegang (hasil alokasi atau isian),
     * agar capaian di daftar survei dan monitoring tetap sama.
     *
     * @throws ValidationException
     */
    private function ensureSurveyIsEditable(Survey $survey): void
    {
        abort_if($survey->status === 'Selesai', 403, 'Survei selesai tidak dapat diedit.');
    }

    /**
     * Variabel dan alokasi hanya berlaku untuk survei PAPI.
     */
    private function ensurePapiSurvey(Survey $survey): void
    {
        abort_if($survey->isCapi(), 404);
    }

    /**
     * Akun pegawai BPS aktif yang dapat ditugaskan sebagai penanggung jawab survei.
     *
     * @return Builder<User>
     */
    private function activePegawai(): Builder
    {
        return User::query()->role('pegawai_bps')->where('is_active', true)->orderBy('name');
    }
}
