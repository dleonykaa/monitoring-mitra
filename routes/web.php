<?php

use App\Http\Controllers\Web\Admin\AdminDashboardController;
use App\Http\Controllers\Web\Admin\MitraDirectoryController;
use App\Http\Controllers\Web\Admin\SurveyManagementController;
use App\Http\Controllers\Web\Admin\UserManagementController;
use App\Http\Controllers\Web\EvidencePhotoController;
use App\Http\Controllers\Web\Mitra\MitraPanelController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\PapiEntriesController;
use App\Http\Controllers\Web\Pegawai\PegawaiPanelController;
use App\Http\Controllers\Web\ProgressMonitoringController;
use App\Models\SurveyEntry;
use App\Models\User;
use App\Notifications\PasswordResetRequestedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;

/**
 * Beranda panel sesuai peran. Akun tanpa peran tidak punya panel, jadi sesinya diakhiri.
 */
$homeFor = fn (User $user): ?string => match ($user->getRoleNames()->first()) {
    'admin' => '/admin/dashboard',
    'pegawai_bps' => '/pegawai/dashboard',
    'mitra' => '/mitra/dashboard',
    default => null,
};
$noRoleMessage = 'Akun Anda belum memiliki peran. Hubungi admin untuk mengaktifkannya.';

Route::get('/', function (Request $request) use ($homeFor, $noRoleMessage) {
    if (! Auth::check()) {
        return redirect('/login');
    }

    if ($home = $homeFor($request->user())) {
        return redirect($home);
    }

    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/login')->withErrors(['email' => $noRoleMessage]);
});

Route::middleware('guest')->group(function () use ($homeFor, $noRoleMessage): void {
    Route::get('/login', fn () => view('ui.login'))->name('login');
    Route::post('/login', function (Request $request) use ($homeFor, $noRoleMessage) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email atau password tidak valid.'])->onlyInput('email');
        }

        /** @var User $user */
        $user = $request->user();
        $home = $homeFor($user);
        if (! $user->is_active || ! $home) {
            Auth::logout();

            return back()->withErrors(['email' => $user->is_active ? $noRoleMessage : 'Akun Anda nonaktif.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect($home);
    })->middleware('throttle:10,1');

    // Lupa kata sandi: sistem tidak mengirim email, jadi permintaan diteruskan ke admin lewat notifikasi.
    // Jawaban selalu sama agar tidak membocorkan email mana yang terdaftar.
    Route::post('/lupa-kata-sandi', function (Request $request) {
        $data = $request->validate(['reset_email' => ['required', 'email']], [], ['reset_email' => 'email']);

        $user = User::query()->where('email', $data['reset_email'])->where('is_active', true)->first();
        if ($user) {
            $admins = User::query()->role('admin')->where('is_active', true)->get()
                // Satu permintaan yang belum dibaca admin tidak perlu digandakan.
                ->reject(fn (User $admin): bool => $admin->unreadNotifications()
                    ->where('type', PasswordResetRequestedNotification::class)
                    ->get()
                    ->contains(fn ($notification): bool => ($notification->data['requester_id'] ?? null) === $user->id));

            Notification::send($admins, new PasswordResetRequestedNotification($user));
        }

        return back()->with('reset_status', 'Permintaan reset kata sandi diteruskan ke admin. Hubungi admin untuk mendapatkan kata sandi baru.');
    })->middleware('throttle:5,1');
});

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/login');
})->middleware('auth');

// Alamat lama halaman pilihan panel.
Route::redirect('/ui', '/');
Route::redirect('/ui/mitra', '/');

Route::middleware('auth')->group(function (): void {
    Route::post('/profile/password', [UserManagementController::class, 'updatePassword']);
    Route::post('/notifications/read', [NotificationController::class, 'markAllRead']);
    Route::get('/bukti/{entry}', [EvidencePhotoController::class, 'show'])->whereNumber('entry');
    Route::delete('/notifications', [NotificationController::class, 'clear']);

    Route::prefix('admin')->middleware('role:admin')->group(function (): void {
        Route::get('/dashboard', [AdminDashboardController::class, 'dashboard']);

        Route::get('/monitoring/progres', [ProgressMonitoringController::class, 'index']);
        Route::get('/monitoring/progres/export', [ProgressMonitoringController::class, 'export']);
        Route::post('/monitoring/progres/import', [ProgressMonitoringController::class, 'store']);
        Route::delete('/monitoring/progres/imports/{fasihImport}', [ProgressMonitoringController::class, 'destroy']);

        Route::get('/surveys', [SurveyManagementController::class, 'index']);
        Route::get('/surveys/create', [SurveyManagementController::class, 'create']);
        Route::post('/surveys', [SurveyManagementController::class, 'store']);
        Route::get('/surveys/{survey}', [SurveyManagementController::class, 'show']);
        Route::get('/surveys/{survey}/edit', [SurveyManagementController::class, 'edit']);
        Route::put('/surveys/{survey}', [SurveyManagementController::class, 'update']);
        Route::delete('/surveys/{survey}', [SurveyManagementController::class, 'destroy']);
        Route::get('/entri-papi', [PapiEntriesController::class, 'index']);
        Route::get('/entri-papi/export', [PapiEntriesController::class, 'export']);
        Route::get('/entri-papi/{entry}', [PapiEntriesController::class, 'show'])->whereNumber('entry');
        Route::get('/surveys/{survey}/entries', fn (int $survey) => redirect('/admin/entri-papi?survey='.$survey));
        Route::get('/surveys/{survey}/entries/{entry}', fn (int $survey, int $entry) => redirect('/admin/entri-papi/'.$entry))->whereNumber('entry');
        Route::get('/surveys/{survey}/variables', [SurveyManagementController::class, 'variables']);
        Route::post('/surveys/{survey}/variables', [SurveyManagementController::class, 'storeVariable']);
        Route::get('/surveys/{survey}/variables/template', [SurveyManagementController::class, 'downloadVariableTemplate']);
        Route::put('/surveys/{survey}/variables/{variable}', [SurveyManagementController::class, 'updateVariable']);
        Route::delete('/surveys/{survey}/variables/{variable}', [SurveyManagementController::class, 'deleteVariable']);
        Route::get('/surveys/{survey}/assignments', [SurveyManagementController::class, 'assignments']);
        Route::post('/surveys/{survey}/assignments', [SurveyManagementController::class, 'storeAssignment']);
        Route::put('/surveys/{survey}/assignments/{assignment}', [SurveyManagementController::class, 'updateAssignment']);
        Route::delete('/surveys/{survey}/assignments/{assignment}', [SurveyManagementController::class, 'deleteAssignment']);
        Route::post('/surveys/{survey}/assignments/import', [SurveyManagementController::class, 'importAssignments']);
        Route::get('/surveys/{survey}/assignments/template', [SurveyManagementController::class, 'downloadAssignmentTemplate']);
        Route::get('/surveys/{survey}/checkpoints', [SurveyManagementController::class, 'checkpoints']);
        Route::post('/surveys/{survey}/checkpoints', [SurveyManagementController::class, 'storeCheckpoint']);
        Route::delete('/surveys/{survey}/checkpoints/{checkpoint}', [SurveyManagementController::class, 'deleteCheckpoint']);
        Route::post('/surveys/{survey}/finish-setup', [SurveyManagementController::class, 'finishSetup']);
        Route::post('/surveys/{survey}/status', [SurveyManagementController::class, 'setStatus']);

        Route::get('/mitra', [MitraDirectoryController::class, 'index']);
        Route::get('/mitra/{user}', [MitraDirectoryController::class, 'show']);

        Route::get('/users', [AdminDashboardController::class, 'users']);
        Route::post('/users', [AdminDashboardController::class, 'storeUser']);
        Route::put('/users/{user}', [AdminDashboardController::class, 'updateUser']);
        Route::delete('/users/{user}', [AdminDashboardController::class, 'deleteUser']);

        Route::get('/logs', [AdminDashboardController::class, 'logs']);

        // Alamat lama yang sudah digabung ke menu baru.
        Route::redirect('/monitoring/fasih', '/admin/monitoring/progres');
        Route::redirect('/monitoring/surveys', '/admin/surveys');
        Route::get('/monitoring/surveys/{survey}', fn (int $survey) => redirect('/admin/surveys/'.$survey));
        Route::redirect('/monitoring/wilayah', '/admin/monitoring/progres');
        Route::redirect('/monitoring/kinerja', '/admin/mitra');
        Route::get('/monitoring/mitra/{user}', fn (int $user) => redirect('/admin/mitra/'.$user));

        Route::get('/profile', fn () => view('panel.profile', ['panelTitle' => 'Admin', 'menuView' => 'panel.admin.menu']));
    });

    Route::prefix('pegawai')->middleware('role:pegawai_bps')->group(function (): void {
        Route::get('/dashboard', [PegawaiPanelController::class, 'dashboard']);
        Route::get('/monitoring/progres', [ProgressMonitoringController::class, 'index']);
        Route::get('/monitoring/progres/export', [ProgressMonitoringController::class, 'export']);
        Route::get('/surveys', [PegawaiPanelController::class, 'surveys']);
        Route::get('/surveys/{survey}', [PegawaiPanelController::class, 'showSurvey']);
        Route::get('/entri-papi', [PapiEntriesController::class, 'index']);
        Route::get('/entri-papi/export', [PapiEntriesController::class, 'export']);
        Route::get('/entri-papi/{entry}', [PapiEntriesController::class, 'show'])->whereNumber('entry');
        Route::get('/surveys/{survey}/entries', fn (int $survey) => redirect('/pegawai/entri-papi?survey='.$survey));
        Route::get('/surveys/{survey}/entries/{entry}', fn (int $survey, int $entry) => redirect('/pegawai/entri-papi/'.$entry))->whereNumber('entry');

        // Notifikasi lama menaut ke riwayat update yang kini berada di data entri survei.
        Route::get('/updates/{entry}', fn (SurveyEntry $entry) => redirect('/pegawai/entri-papi/'.$entry->id));

        Route::get('/profile', fn () => view('panel.profile', ['panelTitle' => 'Pegawai BPS', 'menuView' => 'panel.pegawai.menu']));
    });

    Route::prefix('mitra')->middleware('role:mitra')->group(function (): void {
        Route::get('/dashboard', [MitraPanelController::class, 'dashboard']);
        Route::get('/surveys', [MitraPanelController::class, 'surveys']);
        Route::get('/surveys/{survey}', [MitraPanelController::class, 'showSurvey']);
        Route::get('/surveys/{survey}/entries/create', [MitraPanelController::class, 'createEntry']);
        Route::post('/surveys/{survey}/entries', [MitraPanelController::class, 'storeEntry']);
        Route::get('/entries/{entry}/edit', [MitraPanelController::class, 'editEntry']);
        Route::put('/entries/{entry}', [MitraPanelController::class, 'updateEntry']);

        Route::get('/data-entri', [MitraPanelController::class, 'entries']);
        Route::get('/data-entri/{entry}', [MitraPanelController::class, 'showEntry'])->whereNumber('entry');

        // Alamat lama menu Update Progress.
        Route::redirect('/progress', '/mitra/surveys');
        Route::redirect('/updates', '/mitra/surveys');
        Route::redirect('/entries', '/mitra/data-entri');

        Route::get('/profile', fn () => view('panel.profile', ['panelTitle' => 'Mitra BPS', 'menuView' => 'panel.mitra.menu']));
    });
});
