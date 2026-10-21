<?php

namespace App\Http\Controllers\Web\Pegawai;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Services\SurveyDashboardOverview;
use App\Services\SurveyProgressSummary;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Panel lihat saja untuk pegawai BPS dan pimpinan: dashboard, daftar survei, dan detail survei.
 * Pimpinan melihat seluruh survei; pegawai hanya survei yang ditugaskan kepadanya.
 */
class PegawaiPanelController extends Controller
{
    public function dashboard(Request $request, SurveyDashboardOverview $overview): View
    {
        return view('panel.dashboard', [
            ...$overview->build($request->user()),
            ...$this->panel($request),
        ]);
    }

    public function surveys(Request $request, SurveyProgressSummary $progressSummary): View
    {
        return view('panel.survey-index', [
            'surveys' => $progressSummary->attach(Survey::query()->visibleTo($request->user())->withCount('assignments')->latest()->get()),
            ...$this->panel($request),
        ]);
    }

    public function showSurvey(Request $request, Survey $survey): View
    {
        abort_unless($survey->isVisibleTo($request->user()), 404);

        return view('panel.survey-show', [
            'survey' => $survey->loadDetail(),
            ...$this->panel($request),
        ]);
    }

    /**
     * @return array{panelTitle: string, menuView: string, base: string, canManage: false}
     */
    private function panel(Request $request): array
    {
        $panel = $request->user()->panel();

        return ['panelTitle' => $panel['panelTitle'], 'menuView' => $panel['menuView'], 'base' => $panel['base'], 'canManage' => false];
    }
}
