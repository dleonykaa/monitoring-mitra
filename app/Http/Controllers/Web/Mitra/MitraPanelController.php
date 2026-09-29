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
use App\Notifications\EntryInvalidNotification;
use App\Notifications\NewEntrySubmittedNotification;
use App\Services\EntryAnomalyValidator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class MitraPanelController extends Controller
{
    public function dashboard(Request $request): View
    {
        $assignments = $this->assignments($request)
            ->with(['survey'])
            ->latest()
            ->get();

        $runningAssignments = $assignments->filter(fn (SurveyAssignment $assignment): bool => $assignment->survey->status !== 'Selesai');
        $completedAssignments = $assignments->filter(fn (SurveyAssignment $assignment): bool => $assignment->survey->status === 'Selesai');

        return view('panel.mitra.dashboard', [
            'assignments' => $assignments,
            'runningAssignments' => $runningAssignments,
            'completedAssignments' => $completedAssignments,
            'totalTarget' => $assignments->sum('target'),
            'totalProgress' => $assignments->sum('current_progress'),
            'pendingEntries' => $this->entriesQuery($request)->where('is_valid', false)->where('entry_status', 'submitted')->count(),
            'draftEntries' => $this->entriesQuery($request)->where('entry_status', 'draft')->count(),
        ]);
    }

    public function surveys(Request $request): View
    {
        $assignments = $this->assignments($request)
            ->with(['survey'])
            ->latest()
            ->get();

        return view('panel.mitra.surveys', [
            'runningAssignments' => $assignments->filter(fn (SurveyAssignment $assignment): bool => $assignment->survey->status !== 'Selesai'),
            'completedAssignments' => $assignments->filter(fn (SurveyAssignment $assignment): bool => $assignment->survey->status === 'Selesai'),
        ]);
    }

    public function showSurvey(Request $request, Survey $survey): View
    {
        $assignment = $this->assignmentForSurvey($request, $survey);

        return view('panel.mitra.survey-detail', [
            'assignment' => $assignment->load(['survey.variables', 'entries.values.variable', 'entries.district', 'entries.village']),
            'recentEntries' => $assignment->entries()->with(['values.variable', 'district', 'village'])->latest()->limit(8)->get(),
        ]);
    }

    public function createEntry(Request $request, Survey $survey): View
    {
        return $this->entryFormView($this->assignmentForSurvey($request, $survey), new SurveyEntry);
    }

    public function storeEntry(Request $request, Survey $survey): RedirectResponse
    {
        return $this->persistEntry($request, $this->assignmentForSurvey($request, $survey), new SurveyEntry);
    }

    public function entries(Request $request): View
    {
        $assignments = $this->assignments($request)
            ->with('survey')
            ->latest()
            ->get();
        $selectedSurveyId = $request->integer('survey_id') ?: null;
        $selectedAssignment = $selectedSurveyId
            ? $assignments->firstWhere('survey_id', $selectedSurveyId)
            : null;

        return view('panel.mitra.entries', [
            'assignments' => $assignments,
            'selectedSurveyId' => $selectedSurveyId,
            'selectedAssignment' => $selectedAssignment,
            'entries' => $selectedAssignment
                ? $this->entriesQuery($request)
                    ->where('survey_id', $selectedAssignment->survey_id)
                    ->with(['survey', 'values.variable', 'district', 'village'])
                    ->latest()
                    ->paginate(15)
                    ->withQueryString()
                : null,
        ]);
    }

    public function editEntry(Request $request, SurveyEntry $entry): View
    {
        $this->authorizeEntryAccess($request, $entry);
        abort_unless($this->canEditEntry($entry), 403);

        return $this->entryFormView($entry->assignment->load('survey.variables'), $entry->load('values.variable'));
    }

    public function updateEntry(Request $request, SurveyEntry $entry): RedirectResponse
    {
        $this->authorizeEntryAccess($request, $entry);
        abort_unless($this->canEditEntry($entry), 403);

        return $this->persistEntry($request, $entry->assignment->load('survey.variables'), $entry);
    }

    public function updates(Request $request): View
    {
        return view('panel.mitra.updates', [
            'entries' => $this->entriesQuery($request)
                ->where('entry_status', 'submitted')
                ->with(['survey', 'district', 'village'])
                ->latest('submitted_at')
                ->paginate(15)
                ->withQueryString(),
        ]);
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

    private function persistEntry(Request $request, SurveyAssignment $assignment, SurveyEntry $entry): RedirectResponse
    {
        $wasDraft = ! $entry->exists || $entry->entry_status === 'draft';
        $isExistingEntry = $entry->exists;
        $isSubmit = $request->input('action') === 'submit' || ($isExistingEntry && $entry->entry_status === 'submitted');
        $nextStatus = $isSubmit ? 'submitted' : 'draft';

        $data = $request->validate([
            'district_id' => [$isSubmit ? 'required' : 'nullable', 'exists:districts,id'],
            'village_id' => ['nullable', 'exists:villages,id'],
            'kode_nks' => [$isSubmit ? 'required' : 'nullable', 'string', 'max:50'],
            'sls' => [$isSubmit ? 'required' : 'nullable', 'string', 'max:50'],
            'no_urut_ruta' => [$isSubmit ? 'required' : 'nullable', 'string', 'max:50'],
            'evidence_photo' => [$isSubmit && ! $entry->evidence_photo_path ? 'required' : 'nullable', 'image', 'max:5120'],
            'variables' => ['nullable', 'array'],
            'variables.*' => ['nullable', 'string'],
        ]);

        $this->validateVariableValues($assignment->survey, $request->input('variables', []));

        $photoPath = $entry->evidence_photo_path;
        if ($request->hasFile('evidence_photo')) {
            $photoPath = $request->file('evidence_photo')->store('survey-evidence', 'public');
        }

        DB::transaction(function () use ($request, $assignment, $entry, $data, $photoPath, $isSubmit, $wasDraft, $nextStatus): void {
            $entry->fill([
                'survey_id' => $assignment->survey_id,
                'survey_assignment_id' => $assignment->id,
                'district_id' => $data['district_id'] ?? null,
                'village_id' => $data['village_id'] ?? null,
                'kode_nks' => $data['kode_nks'] ?? $entry->kode_nks,
                'sls' => $data['sls'] ?? $entry->sls,
                'no_urut_ruta' => $data['no_urut_ruta'] ?? $entry->no_urut_ruta,
                'ppl' => $assignment->mitra->name,
                'evidence_photo_path' => $photoPath,
                'entry_status' => $nextStatus,
                'submitted_at' => $isSubmit ? ($entry->submitted_at ?? now()) : null,
            ])->save();

            foreach ($assignment->survey->variables as $variable) {
                EntryVariableValue::query()->updateOrCreate(
                    ['survey_entry_id' => $entry->id, 'survey_variable_id' => $variable->id],
                    ['value' => $request->input('variables.'.$variable->id)]
                );
            }

            if ($isSubmit) {
                EntryAnomalyValidator::apply($entry);
            }

            if ($isSubmit && $wasDraft) {
                $assignment->increment('current_progress');
                $this->syncSurveyCompletion($assignment->survey);
            }
        });

        if ($isSubmit) {
            $entry->refresh();
            if ($entry->is_valid === false) {
                $entry->assignment->mitra->notify(new EntryInvalidNotification($entry->load(['survey', 'assignment.mitra'])));
            }
        }

        if ($isSubmit && $wasDraft) {
            $this->notifyPegawai($assignment->survey, $entry);
            ActivityLog::query()->create([
                'user_id' => $request->user()->id,
                'action' => 'mitra.entry.submit',
                'description' => 'Mitra submit entri #'.$entry->id.' pada '.$assignment->survey->title,
            ]);
        }

        return redirect('/mitra/surveys/'.$assignment->survey_id)->with('status', $isSubmit ? 'Progress ditambahkan!' : 'Draft entri berhasil disimpan.');
    }

    private function validateVariableValues(Survey $survey, array $values): void
    {
        foreach ($survey->variables as $variable) {
            $value = (string) ($values[$variable->id] ?? '');
            if ($variable->data_type === 'number' && $value !== '' && ! preg_match('/^-?\d+(?:[.,]\d+)*$/', $value)) {
                abort(422, 'Format angka tidak sesuai untuk variabel '.$variable->name.'. Contoh: '.$variable->example_format);
            }
        }
    }

    private function canEditEntry(SurveyEntry $entry): bool
    {
        return $entry->entry_status === 'draft' || ($entry->entry_status === 'submitted' && $entry->is_valid !== true);
    }

    private function authorizeEntryAccess(Request $request, SurveyEntry $entry): void
    {
        abort_unless($entry->assignment()->where('mitra_id', $request->user()->id)->exists(), 403);
    }

    private function assignmentForSurvey(Request $request, Survey $survey): SurveyAssignment
    {
        return $this->assignments($request)
            ->where('survey_id', $survey->id)
            ->with('survey.variables')
            ->firstOrFail();
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

    private function syncSurveyCompletion(Survey $survey): void
    {
        $target = (int) $survey->total_target;
        if ($target > 0 && $survey->assignments()->sum('current_progress') >= $target) {
            $survey->update(['status' => 'Selesai']);
        }
    }

    private function notifyPegawai(Survey $survey, SurveyEntry $entry): void
    {
        $pegawaiUsers = User::query()
            ->whereHas('teams', fn (Builder $query) => $query->whereKey($survey->team_id))
            ->role('pegawai_bps')
            ->get();

        Notification::send($pegawaiUsers, new NewEntrySubmittedNotification($survey, $entry));
    }
}
