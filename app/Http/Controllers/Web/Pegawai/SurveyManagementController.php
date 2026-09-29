<?php

namespace App\Http\Controllers\Web\Pegawai;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignMitraRequest;
use App\Http\Requests\StoreSurveyRequest;
use App\Http\Requests\StoreSurveyVariableRequest;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\User;
use App\Notifications\MitraAssignedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Maatwebsite\Excel\Facades\Excel;

class SurveyManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamIds = $request->user()->teams()->pluck('teams.id');

        return response()->json(Survey::query()->with('assignments.mitra')->whereIn('team_id', $teamIds)->latest()->paginate(20));
    }

    public function store(StoreSurveyRequest $request): JsonResponse
    {
        $survey = Survey::query()->create($request->validated() + ['created_by' => $request->user()->id]);

        return response()->json($survey, 201);
    }

    public function addVariable(StoreSurveyVariableRequest $request, Survey $survey): JsonResponse
    {
        $variable = $survey->variables()->create($request->validated());

        return response()->json($variable, 201);
    }

    public function assignMitra(AssignMitraRequest $request, Survey $survey): JsonResponse
    {
        $assignment = SurveyAssignment::query()->updateOrCreate(
            ['survey_id' => $survey->id, 'mitra_id' => $request->integer('mitra_id')],
            ['target' => $request->integer('target')]
        );

        $mitra = User::query()->findOrFail($request->integer('mitra_id'));
        $mitra->notify(new MitraAssignedNotification($survey));

        return response()->json($assignment, 201);
    }

    public function importAssignments(Request $request, Survey $survey): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,csv']]);

        $rows = Excel::toArray([], $request->file('file'))[0] ?? [];
        $created = 0;

        foreach ($rows as $row) {
            if (! isset($row[0], $row[1])) {
                continue;
            }

            $mitra = User::query()->where('email', (string) $row[0])->first();
            if (! $mitra) {
                continue;
            }

            SurveyAssignment::query()->updateOrCreate(
                ['survey_id' => $survey->id, 'mitra_id' => $mitra->id],
                ['target' => (int) $row[1]]
            );
            $mitra->notify(new MitraAssignedNotification($survey));
            $created++;
        }

        return response()->json(['message' => 'Import selesai', 'processed' => $created]);
    }
}
