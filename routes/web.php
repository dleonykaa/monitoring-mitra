<?php

use App\Http\Controllers\Web\Admin\AdminDashboardController;
use App\Http\Controllers\Web\Admin\UserManagementController;
use App\Http\Controllers\Web\Mitra\MitraPanelController;
use App\Http\Controllers\Web\Pegawai\PegawaiPanelController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! Auth::check()) {
        return redirect('/login');
    }

    $role = Auth::user()?->getRoleNames()->first();

    return match ($role) {
        'admin' => redirect('/admin/dashboard'),
        'pegawai_bps' => redirect('/pegawai/dashboard'),
        'mitra' => redirect('/mitra/dashboard'),
        default => view('ui.index'),
    };
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', fn () => view('ui.login'))->name('login');
    Route::post('/login', function (Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email atau password tidak valid.'])->onlyInput('email');
        }

        /** @var User $user */
        $user = $request->user();
        if (! $user->is_active) {
            Auth::logout();
            return back()->withErrors(['email' => 'Akun Anda nonaktif.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return match ($user->getRoleNames()->first()) {
            'admin' => redirect('/admin/dashboard'),
            'pegawai_bps' => redirect('/pegawai/dashboard'),
            'mitra' => redirect('/mitra/dashboard'),
            default => redirect('/ui/mitra'),
        };
    });
});

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/login');
})->middleware('auth');

Route::get('/ui', fn () => view('ui.index'))->middleware('auth');
Route::get('/ui/mitra', fn () => redirect('/mitra/dashboard'))->middleware('auth');

Route::middleware('auth')->group(function (): void {
    Route::post('/profile/password', [UserManagementController::class, 'updatePassword']);

    Route::prefix('admin')->middleware('role:admin')->group(function (): void {
        Route::get('/dashboard', [AdminDashboardController::class, 'dashboard']);
        Route::get('/users', [AdminDashboardController::class, 'users']);
        Route::post('/users', [AdminDashboardController::class, 'storeUser']);
        Route::put('/users/{user}', [AdminDashboardController::class, 'updateUser']);
        Route::delete('/users/{user}', [AdminDashboardController::class, 'deleteUser']);
        Route::get('/roles', [AdminDashboardController::class, 'roles']);
        Route::put('/roles/{role}', [AdminDashboardController::class, 'updateRolePermissions']);
        Route::get('/teams', [AdminDashboardController::class, 'teams']);
        Route::post('/teams', [AdminDashboardController::class, 'storeTeam']);
        Route::get('/teams/{team}', [AdminDashboardController::class, 'teamDetail']);
        Route::post('/teams/{team}/assign', [AdminDashboardController::class, 'assignPegawai']);
        Route::delete('/teams/{team}/users/{user}', [AdminDashboardController::class, 'detachPegawai']);
        Route::get('/regions', [AdminDashboardController::class, 'regions']);
        Route::post('/regions/districts', [AdminDashboardController::class, 'storeDistrict']);
        Route::put('/regions/districts/{district}', [AdminDashboardController::class, 'updateDistrict']);
        Route::post('/regions/villages', [AdminDashboardController::class, 'storeVillage']);
        Route::put('/regions/villages/{village}', [AdminDashboardController::class, 'updateVillage']);
        Route::delete('/regions/villages/{village}', [AdminDashboardController::class, 'deleteVillage']);
        Route::get('/logs', [AdminDashboardController::class, 'logs']);
        Route::get('/monitoring/surveys', [AdminDashboardController::class, 'monitoringSurveys']);
        Route::get('/monitoring/surveys/{survey}', [AdminDashboardController::class, 'monitoringSurveyDetail']);
        Route::get('/monitoring/wilayah', [AdminDashboardController::class, 'monitoringWilayah']);
        Route::get('/monitoring/kinerja', [AdminDashboardController::class, 'monitoringKinerja']);
        Route::get('/monitoring/kinerja/export', [AdminDashboardController::class, 'exportKinerja']);
        Route::get('/monitoring/mitra/{user}', [AdminDashboardController::class, 'monitoringMitraProfile']);
        Route::get('/profile', function () {
            return view('panel.profile', [
                'panelTitle' => 'Admin',
                'menuHtml' => '<a href="/admin/dashboard">Dashboard</a><a href="/admin/users">Manajemen Pengguna</a><a href="/admin/roles">Role & Hak Akses</a><a href="/admin/teams">Tim Kerja</a><a href="/admin/regions">Wilayah</a><a href="/admin/monitoring/surveys">Monitoring Survei</a><a href="/admin/monitoring/wilayah">Peta Wilayah</a><a href="/admin/monitoring/kinerja">Kinerja Mitra</a><a href="/admin/logs">Log Aktivitas</a>',
            ]);
        });
    });

    Route::prefix('pegawai')->middleware('role:pegawai_bps')->group(function (): void {
        Route::get('/dashboard', [PegawaiPanelController::class, 'dashboard']);
        Route::get('/surveys', [PegawaiPanelController::class, 'surveys']);
        Route::get('/surveys/create', [PegawaiPanelController::class, 'createSurvey']);
        Route::post('/surveys', [PegawaiPanelController::class, 'storeSurvey']);
        Route::get('/surveys/{survey}', [PegawaiPanelController::class, 'showSurvey']);
        Route::get('/surveys/{survey}/edit', [PegawaiPanelController::class, 'editSurvey']);
        Route::put('/surveys/{survey}', [PegawaiPanelController::class, 'updateSurvey']);
        Route::delete('/surveys/{survey}', [PegawaiPanelController::class, 'deleteSurvey']);
        Route::get('/surveys/{survey}/variables', [PegawaiPanelController::class, 'variables']);
        Route::post('/surveys/{survey}/variables', [PegawaiPanelController::class, 'storeVariable']);
        Route::get('/surveys/{survey}/variables/template', [PegawaiPanelController::class, 'downloadVariableTemplate']);
        Route::put('/surveys/{survey}/variables/{variable}', [PegawaiPanelController::class, 'updateVariable']);
        Route::delete('/surveys/{survey}/variables/{variable}', [PegawaiPanelController::class, 'deleteVariable']);
        Route::put('/surveys/{survey}/validation-formula', [PegawaiPanelController::class, 'updateValidationFormula']);
        Route::get('/surveys/{survey}/assignments', [PegawaiPanelController::class, 'assignments']);
        Route::post('/surveys/{survey}/assignments', [PegawaiPanelController::class, 'storeAssignment']);
        Route::put('/surveys/{survey}/assignments/{assignment}', [PegawaiPanelController::class, 'updateAssignment']);
        Route::delete('/surveys/{survey}/assignments/{assignment}', [PegawaiPanelController::class, 'deleteAssignment']);
        Route::post('/surveys/{survey}/assignments/import', [PegawaiPanelController::class, 'importAssignments']);
        Route::get('/surveys/{survey}/assignments/template', [PegawaiPanelController::class, 'downloadAssignmentTemplate']);
        Route::get('/surveys/{survey}/checkpoints', [PegawaiPanelController::class, 'checkpoints']);
        Route::post('/surveys/{survey}/checkpoints', [PegawaiPanelController::class, 'storeCheckpoint']);
        Route::put('/surveys/{survey}/checkpoints/{checkpoint}', [PegawaiPanelController::class, 'updateCheckpoint']);
        Route::delete('/surveys/{survey}/checkpoints/{checkpoint}', [PegawaiPanelController::class, 'deleteCheckpoint']);
        Route::post('/surveys/{survey}/finish-setup', [PegawaiPanelController::class, 'finishSetup']);
        Route::post('/surveys/{survey}/status', [PegawaiPanelController::class, 'setStatus']);
        Route::get('/updates', [PegawaiPanelController::class, 'updates']);
        Route::get('/updates/{entry}', [PegawaiPanelController::class, 'updateDetail']);
        Route::get('/mitra', [PegawaiPanelController::class, 'mitraList']);
        Route::get('/mitra/{user}', [PegawaiPanelController::class, 'mitraProfile']);
        Route::get('/entries', [PegawaiPanelController::class, 'entries']);
        Route::get('/entries/export', [PegawaiPanelController::class, 'exportEntries']);
        Route::get('/notifications', [PegawaiPanelController::class, 'notifications']);
        Route::get('/profile', function () {
            return view('panel.profile', [
                'panelTitle' => 'Pegawai BPS',
                'menuHtml' => '<a href="/pegawai/dashboard">Dashboard Tim</a><a href="/pegawai/surveys">Survei</a><a href="/pegawai/updates">Riwayat Update</a><a href="/pegawai/mitra">Daftar Mitra</a><a href="/pegawai/entries">Data Entri</a>',
            ]);
        });
    });

    Route::prefix('mitra')->middleware('role:mitra')->group(function (): void {
        Route::get('/dashboard', [MitraPanelController::class, 'dashboard']);
        Route::get('/surveys', [MitraPanelController::class, 'surveys']);
        Route::get('/surveys/{survey}', [MitraPanelController::class, 'showSurvey']);
        Route::get('/surveys/{survey}/entries/create', [MitraPanelController::class, 'createEntry']);
        Route::post('/surveys/{survey}/entries', [MitraPanelController::class, 'storeEntry']);
        Route::get('/entries', [MitraPanelController::class, 'entries']);
        Route::get('/entries/{entry}/edit', [MitraPanelController::class, 'editEntry']);
        Route::put('/entries/{entry}', [MitraPanelController::class, 'updateEntry']);
        Route::get('/updates', [MitraPanelController::class, 'updates']);
        Route::get('/profile', function () {
            return view('panel.profile', [
                'panelTitle' => 'Mitra BPS',
                'menuHtml' => '<a href="/mitra/dashboard">Dashboard</a><a href="/mitra/surveys">Survei</a><a href="/mitra/entries">Data Entri</a><a href="/mitra/updates">Riwayat Update</a>',
            ]);
        });
    });
});
