<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $assignments = $request->user()
            ->assignments()
            ->with('survey:id,title,start_date,end_date,status')
            ->get();

        return response()->json($assignments);
    }

    public function show(Request $request, int $surveyId): JsonResponse
    {
        $assignment = $request->user()->assignments()
            ->with(['survey.variables', 'survey.entries'])
            ->where('survey_id', $surveyId)
            ->firstOrFail();

        return response()->json($assignment);
    }
}
