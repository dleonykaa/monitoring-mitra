<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSurveyEntryRequest;
use App\Models\EntryVariableValue;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyEntry;
use App\Models\User;
use App\Notifications\EntryInvalidNotification;
use App\Notifications\NewEntrySubmittedNotification;
use App\Services\EntryAnomalyValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SurveyEntryController extends Controller
{
    public function store(StoreSurveyEntryRequest $request, int $surveyId): JsonResponse
    {
        $assignment = SurveyAssignment::query()
            ->where('survey_id', $surveyId)
            ->where('mitra_id', $request->user()->id)
            ->firstOrFail();

        $survey = Survey::query()->with('variables')->findOrFail($surveyId);

        $photoPath = $request->file('evidence_photo')->store('survey-evidence', 'public');

        $entry = DB::transaction(function () use ($request, $assignment, $survey, $photoPath): SurveyEntry {
            $entry = SurveyEntry::query()->create([
                'survey_id' => $survey->id,
                'survey_assignment_id' => $assignment->id,
                'district_id' => $request->integer('district_id'),
                'village_id' => $request->integer('village_id') ?: null,
                'respondent_name' => $request->string('respondent_name')->value(),
                'evidence_photo_path' => $photoPath,
                'entry_status' => 'submitted',
                'submitted_at' => now(),
            ]);

            foreach ($request->input('variables', []) as $item) {
                $variable = $survey->variables->firstWhere('id', (int) $item['survey_variable_id']);
                if (! $variable) {
                    continue;
                }

                $value = (string) ($item['value'] ?? '');
                if ($variable->data_type === 'number' && $value !== '' && ! preg_match('/^-?\d+(?:[.,]\d+)*$/', $value)) {
                    abort(422, 'Format angka tidak sesuai untuk variabel '.$variable->name);
                }

                EntryVariableValue::query()->create([
                    'survey_entry_id' => $entry->id,
                    'survey_variable_id' => $variable->id,
                    'value' => $value,
                ]);
            }

            $assignment->increment('current_progress');

            $totalProgress = (int) $survey->assignments()->sum('current_progress');
            if ($totalProgress >= $survey->total_target) {
                $survey->update(['status' => 'Selesai']);
            }

            EntryAnomalyValidator::apply($entry);

            return $entry;
        });

        $entry->refresh();
        if ($entry->is_valid === false) {
            $assignment->mitra->notify(new EntryInvalidNotification($entry->load(['survey', 'assignment.mitra'])));
        }

        $pegawaiUsers = User::query()
            ->whereHas('teams', fn ($query) => $query->whereKey($survey->team_id))
            ->role('pegawai_bps')
            ->get();

        Notification::send($pegawaiUsers, new NewEntrySubmittedNotification($survey, $entry));

        return response()->json($entry->load('values'), 201);
    }
}
