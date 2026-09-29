<?php

namespace App\Http\Controllers\Web\Pegawai;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Models\SurveyEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamIds = $request->user()->teams()->pluck('teams.id');
        $surveyIds = Survey::query()->whereIn('team_id', $teamIds)->pluck('id');

        return response()->json([
            'total_surveys' => $surveyIds->count(),
            'total_entries' => SurveyEntry::query()->whereIn('survey_id', $surveyIds)->count(),
            'latest_entries' => SurveyEntry::query()->whereIn('survey_id', $surveyIds)->latest()->limit(20)->get(),
        ]);
    }
}
