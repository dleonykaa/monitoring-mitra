<?php

namespace App\Http\Controllers\Web\Mitra;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\District;
use App\Models\EntryVariableValue;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyEntry;
use App\Models\User;
use App\Models\Village;
use App\Notifications\NewEntrySubmittedNotification;
use App\Services\MitraSurveyHoldings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Panel mitra: Dashboard (ringkasan saja), Daftar Survei (tempat mengisi ruta PAPI), dan Data Entri (entri terkirim).
 */
class MitraPanelController extends Controller
{
    /**
     * Dashboard berisi seluruh survei yang ditugaskan: PAPI dari alokasi, CAPI dari data FASIH.
     * Survei Draft belum ditampilkan karena admin belum menjalankannya.
     */
    public function dashboard(Request $request, MitraSurveyHoldings $holdings): View
    {
        $items = $this->visibleHoldings($request, $holdings);

        return view('panel.mitra.dashboard', [
            'runningItems' => $items->filter(fn (array $item): bool => $item['survey']->status === 'Berjalan')->values(),
            'completedItems' => $items->filter(fn (array $item): bool => $item['survey']->status === 'Selesai')->values(),
            'entryCounts' => $this->entryCountsBySurvey($request),
        ]);
    }

    /**
     * Daftar Survei: survei yang ditugaskan, dipisah Berjalan dan Selesai. Pengisian ruta dimulai dari sini.
     */
    public function surveys(Request $request, MitraSurveyHoldings $holdings): View
    {
        $status = $request->validate(['status' => ['nullable', 'in:Berjalan,Selesai']])['status'] ?? 'Berjalan';
        $items = $this->visibleHoldings($request, $holdings);

        return view('panel.mitra.surveys', [
            'status' => $status,
            'statusCounts' => collect(['Berjalan', 'Selesai'])
                ->mapWithKeys(fn (string $value): array => [$value => $items->filter(fn (array $item): bool => $item['survey']->status === $value)->count()])
                ->all(),
            'items' => $items->filter(fn (array $item): bool => $item['survey']->status === $status)
                ->sortBy(fn (array $item) => $item['survey']->end_date, SORT_REGULAR, $status === 'Selesai')
                ->values(),
            'entryCounts' => $this->entryCountsBySurvey($request),
        ]);
    }

    /**
     * Data Entri: entri milik mitra yang sudah dikirim. Hanya bisa dilihat.
     */
    public function entries(Request $request): View
    {
        $data = $request->validate([
            'survey' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $filters = ['survey' => isset($data['survey']) ? (int) $data['survey'] : null, 'q' => trim((string) ($data['q'] ?? ''))];

        $entries = $this->entriesQuery($request)
            ->where('entry_status', SurveyEntry::STATUS_SUBMITTED)
            ->when($filters['survey'], fn (Builder $query, int $surveyId) => $query->where('survey_id', $surveyId))
            ->when($filters['q'] !== '', fn (Builder $query) => $query->where(fn (Builder $search) => $search
                ->where('no_urut_ruta', 'like', '%'.$filters['q'].'%')
                ->orWhere('sls', 'like', '%'.$filters['q'].'%')
                ->orWhereHas('village', fn (Builder $village) => $village->where('name', 'like', '%'.$filters['q'].'%'))))
            ->with(['survey', 'district', 'village'])
            ->latest('submitted_at')
            ->paginate(20)
            ->withQueryString();

        return view('panel.mitra.entries', [
            'entries' => $entries,
            'filters' => $filters,
            'surveys' => Survey::query()
                ->whereIn('id', $this->activeAssignments($request)->select('survey_id'))
                ->orderBy('title')
                ->get(['id', 'title']),
        ]);
    }

    public function showEntry(Request $request, SurveyEntry $entry): View
    {
        abort_unless($entry->assignment()->where('mitra_id', $request->user()->id)->exists(), 403);
        abort_unless($entry->entry_status === SurveyEntry::STATUS_SUBMITTED, 404);

        return view('panel.survey-entry-detail', [
            'panelTitle' => 'Mitra BPS',
            'menuView' => 'panel.mitra.menu',
            'base' => '/mitra',
            'backUrl' => '/mitra/data-entri?survey='.$entry->survey_id,
            'backLabel' => 'Data Entri',
            'survey' => $entry->survey->load('variables'),
            'entry' => $entry->load(['assignment.mitra', 'district', 'village', 'values']),
        ]);
    }

    public function showSurvey(Request $request, Survey $survey): View
    {
        $assignment = $this->assignmentForSurvey($request, $survey);

        $entries = $assignment->entries()
            ->with(['district', 'village'])
            ->orderByRaw("case entry_status when 'draft' then 0 when 'open' then 1 else 2 end")
            ->latest('updated_at')
            ->get();

        return view('panel.mitra.survey-detail', [
            'assignment' => $assignment,
            'entries' => $entries,
            'statusCounts' => $this->statusCounts($assignment, $entries),
            'canAddEntry' => $this->hasUnallocatedSlot($assignment),
            'passedCheckpoint' => $assignment->survey->passedCheckpoint(),
            'nextCheckpoint' => $assignment->survey->nextCheckpoint(),
        ]);
    }

    public function createEntry(Request $request, Survey $survey): View|RedirectResponse
    {
        $assignment = $this->assignmentForSurvey($request, $survey);
        if (! $this->hasUnallocatedSlot($assignment)) {
            return redirect('/mitra/surveys/'.$survey->id)->withErrors(['entry' => 'Seluruh target sudah memiliki entri. Lanjutkan entri Open atau Draft yang ada.']);
        }

        return $this->entryFormView($assignment, new SurveyEntry);
    }

    public function storeEntry(Request $request, Survey $survey): RedirectResponse
    {
        $assignment = $this->assignmentForSurvey($request, $survey);
        if (! $this->hasUnallocatedSlot($assignment)) {
            return redirect('/mitra/surveys/'.$survey->id)->withErrors(['entry' => 'Seluruh target sudah memiliki entri. Lanjutkan entri Open atau Draft yang ada.']);
        }

        return $this->persistEntry($request, $assignment, new SurveyEntry);
    }

    public function editEntry(Request $request, SurveyEntry $entry): View
    {
        $this->authorizeEntryAccess($request, $entry);

        return $this->entryFormView($entry->assignment->load('survey.variables'), $entry->load('values.variable'));
    }

    public function updateEntry(Request $request, SurveyEntry $entry): RedirectResponse
    {
        $this->authorizeEntryAccess($request, $entry);

        return $this->persistEntry($request, $entry->assignment->load('survey.variables'), $entry);
    }

    private function entryFormView(SurveyAssignment $assignment, SurveyEntry $entry): View
    {
        $entry->loadMissing('values.variable');

        return view('panel.mitra.entry-form', [
            'assignment' => $assignment->load('survey.variables'),
            'entry' => $entry,
            'districts' => District::query()->orderBy('name')->get(),
            'villages' => Village::query()->orderBy('name')->get(),
            'valueMap' => $entry->exists ? $entry->values->keyBy('survey_variable_id') : collect(),
        ]);
    }

    /**
     * "Simpan Draft" menyimpan isian apa adanya. "Selesai" mewajibkan seluruh identitas,
     * seluruh variabel isian, dan foto bukti pencacahan terisi. Tidak ada validasi kualitas data.
     */
    private function persistEntry(Request $request, SurveyAssignment $assignment, SurveyEntry $entry): RedirectResponse
    {
        $isSubmit = $request->input('action') === 'submit';
        $requiredWhenSubmit = $isSubmit ? 'required' : 'nullable';

        // Ruta hasil alokasi admin sudah punya identitas; mitra tidak perlu (dan tidak bisa) mengubahnya.
        if ($entry->hasAllocatedIdentity()) {
            $request->merge([
                'district_id' => $entry->district_id,
                'village_id' => $entry->village_id,
                'sls' => $entry->sls,
                'no_urut_ruta' => $entry->no_urut_ruta,
            ]);
        }
        $variables = $assignment->survey->variables;

        $data = $request->validate([
            'district_id' => [$requiredWhenSubmit, 'exists:districts,id'],
            'village_id' => [$requiredWhenSubmit, 'exists:villages,id'],
            'sls' => [$requiredWhenSubmit, 'string', 'max:50'],
            'no_urut_ruta' => [$requiredWhenSubmit, 'string', 'max:50'],
            'evidence_photo' => [$isSubmit && ! $entry->evidence_photo_path ? 'required' : 'nullable', 'image', 'max:5120'],
            'variables' => ['nullable', 'array'],
            ...$variables->mapWithKeys(fn ($variable): array => [
                'variables.'.$variable->id => [$requiredWhenSubmit, 'string', 'max:1000'],
            ])->all(),
        ], [
            'evidence_photo.required' => 'Foto bukti pencacahan wajib diunggah sebelum entri diselesaikan.',
        ], [
            'district_id' => 'kecamatan',
            'village_id' => 'kelurahan/pulau',
            'sls' => 'SLS',
            'no_urut_ruta' => 'no urut ruta',
            'evidence_photo' => 'foto bukti pencacahan',
            ...$variables->mapWithKeys(fn ($variable): array => ['variables.'.$variable->id => $variable->name])->all(),
        ]);

        $this->validateVariableValues($assignment->survey, $request->input('variables', []));

        $photoPath = $entry->evidence_photo_path;
        if ($request->hasFile('evidence_photo')) {
            $photoPath = $request->file('evidence_photo')->store('survey-evidence', SurveyEntry::PHOTO_DISK);
        }

        DB::transaction(function () use ($request, $assignment, $entry, $data, $photoPath, $isSubmit, $variables): void {
            $entry->fill([
                'survey_id' => $assignment->survey_id,
                'survey_assignment_id' => $assignment->id,
                'district_id' => $data['district_id'] ?? null,
                'village_id' => $data['village_id'] ?? null,
                'sls' => $data['sls'] ?? null,
                'no_urut_ruta' => $data['no_urut_ruta'] ?? null,
                'ppl' => $assignment->mitra->name,
                'evidence_photo_path' => $photoPath,
                'entry_status' => $isSubmit ? SurveyEntry::STATUS_SUBMITTED : SurveyEntry::STATUS_DRAFT,
                'submitted_at' => $isSubmit ? now() : null,
            ])->save();

            foreach ($variables as $variable) {
                EntryVariableValue::query()->updateOrCreate(
                    ['survey_entry_id' => $entry->id, 'survey_variable_id' => $variable->id],
                    ['value' => $request->input('variables.'.$variable->id)]
                );
            }

            if ($isSubmit) {
                $assignment->increment('current_progress');
            }
        });

        if ($isSubmit) {
            $this->notifyStaff($assignment->survey, $entry);
            ActivityLog::query()->create([
                'user_id' => $request->user()->id,
                'action' => 'mitra.entry.submit',
                'description' => 'Mitra menyelesaikan entri #'.$entry->id.' pada '.$assignment->survey->title,
            ]);
        }

        return redirect('/mitra/surveys/'.$assignment->survey_id)->with('status', $isSubmit ? 'Entri dikirim. Progres Anda bertambah.' : 'Draft disimpan. Anda bisa melanjutkannya kapan saja.');
    }

    /**
     * @param  array<int|string, mixed>  $values
     */
    private function validateVariableValues(Survey $survey, array $values): void
    {
        $messages = [];
        foreach ($survey->variables as $variable) {
            $value = (string) ($values[$variable->id] ?? '');
            if ($variable->data_type === 'number' && $value !== '' && ! preg_match('/^-?\d+(?:[.,]\d+)*$/', $value)) {
                $messages['variables.'.$variable->id] = $variable->name.' harus berupa angka'.($variable->example_format ? ', contoh: '.$variable->example_format : '').'.';
            }
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    /**
     * Open mencakup entri alokasi yang belum disentuh ditambah sisa target yang belum punya entri.
     *
     * @param  Collection<int, SurveyEntry>  $entries
     * @return array{open: int, draft: int, submitted: int}
     */
    private function statusCounts(SurveyAssignment $assignment, Collection $entries): array
    {
        return [
            'open' => $entries->where('entry_status', SurveyEntry::STATUS_OPEN)->count() + max(0, (int) $assignment->target - $entries->count()),
            'draft' => $entries->where('entry_status', SurveyEntry::STATUS_DRAFT)->count(),
            'submitted' => $entries->where('entry_status', SurveyEntry::STATUS_SUBMITTED)->count(),
        ];
    }

    private function hasUnallocatedSlot(SurveyAssignment $assignment): bool
    {
        return $assignment->survey->status === 'Berjalan'
            && $assignment->entries()->count() < (int) $assignment->target;
    }

    /**
     * Mitra hanya boleh mengisi entri miliknya yang belum Selesai, selama survei masih berjalan.
     */
    private function authorizeEntryAccess(Request $request, SurveyEntry $entry): void
    {
        abort_unless($entry->assignment()->where('mitra_id', $request->user()->id)->exists(), 403);
        abort_unless($entry->isEditableByMitra() && $entry->survey?->status === 'Berjalan', 403);
    }

    /**
     * Survei yang ditugaskan (PAPI dari alokasi, CAPI dari data FASIH) dan sudah dijalankan admin.
     *
     * @return Collection<int, array{survey: Survey, target: int, progress: int, percent: float, unit: string}>
     */
    private function visibleHoldings(Request $request, MitraSurveyHoldings $holdings): Collection
    {
        return $holdings->forMitra(collect([$request->user()]))->get($request->user()->id, collect())
            ->reject(fn (array $item): bool => $item['survey']->status === 'Draft')
            ->values();
    }

    private function assignmentForSurvey(Request $request, Survey $survey): SurveyAssignment
    {
        return $this->activeAssignments($request)
            ->where('survey_id', $survey->id)
            ->with('survey.variables')
            ->firstOrFail();
    }

    /**
     * Alokasi pada survei PAPI yang sudah dijalankan admin (bukan Draft).
     */
    private function activeAssignments(Request $request): Builder
    {
        return $this->assignments($request)
            ->whereHas('survey', fn (Builder $query) => $query->where('type', Survey::TYPE_PAPI)->where('status', '!=', 'Draft'));
    }

    /**
     * @return Collection<int, Collection<string, int>>
     */
    private function entryCountsBySurvey(Request $request): Collection
    {
        return $this->entriesQuery($request)
            ->selectRaw('survey_id, entry_status, count(*) as total')
            ->groupBy('survey_id', 'entry_status')
            ->get()
            ->groupBy('survey_id')
            ->map(fn (Collection $rows): Collection => $rows->pluck('total', 'entry_status')->map(fn ($total): int => (int) $total));
    }

    private function assignments(Request $request): Builder
    {
        return SurveyAssignment::query()->where('mitra_id', $request->user()->id);
    }

    private function entriesQuery(Request $request): Builder
    {
        return SurveyEntry::query()
            ->whereHas('assignment', fn (Builder $query) => $query->where('mitra_id', $request->user()->id));
    }

    /**
     * Seluruh admin dan pegawai aktif diberi tahu setiap mitra menyelesaikan entri.
     */
    private function notifyStaff(Survey $survey, SurveyEntry $entry): void
    {
        Notification::send(
            User::query()->role(['admin', 'pegawai_bps'])->where('is_active', true)->get(),
            new NewEntrySubmittedNotification($survey, $entry)
        );
    }
}
