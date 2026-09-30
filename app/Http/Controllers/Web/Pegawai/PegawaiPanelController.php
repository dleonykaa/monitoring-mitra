<?php

namespace App\Http\Controllers\Web\Pegawai;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Services\SurveyDashboardOverview;
use App\Services\SurveyProgressSummary;
use Illuminate\View\View;

/**
 * Panel pegawai bersifat lihat saja: dashboard, daftar survei, dan detail survei.
 */
class PegawaiPanelController extends Controller
{
    public function dashboard(SurveyDashboardOverview $overview): View
    {
        return view('panel.dashboard', [
            ...$overview->build(),
            'panelTitle' => 'Pegawai BPS',
            'menuView' => 'panel.pegawai.menu',
            'base' => '/pegawai',
            'canManage' => false,
        ]);
    }

    public function surveys(SurveyProgressSummary $progressSummary): View
    {
        return view('panel.survey-index', [
            'surveys' => $progressSummary->attach(Survey::query()->withCount('assignments')->latest()->get()),
            'panelTitle' => 'Pegawai BPS',
            'menuView' => 'panel.pegawai.menu',
            'base' => '/pegawai',
            'canManage' => false,
        ]);
    }

    public function showSurvey(Survey $survey): View
    {
        return view('panel.survey-show', [
            'survey' => $survey->loadDetail(),
            'panelTitle' => 'Pegawai BPS',
            'menuView' => 'panel.pegawai.menu',
            'base' => '/pegawai',
            'canManage' => false,
        ]);
    }
}
