<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Survey;
use App\Models\User;
use App\Notifications\PegawaiAssignedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Admin menunjuk pegawai BPS sebagai penanggung jawab (PML) survei, di samping alokasi mitranya.
 * Pegawai yang ditunjuk diberi tahu dan menerima notifikasi entri mitra survei tersebut.
 */
class SurveyPegawaiController extends Controller
{
    public function store(Request $request, Survey $survey): RedirectResponse
    {
        $this->ensureSurveyIsEditable($survey);

        $data = $request->validate([
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('is_active', true)->whereNull('deleted_at'),
                Rule::unique('survey_pegawai', 'user_id')->where('survey_id', $survey->id),
            ],
        ], [
            'user_id.required' => 'Pilih pegawai yang akan ditugaskan.',
            'user_id.exists' => 'Pegawai tidak ditemukan atau tidak aktif.',
            'user_id.unique' => 'Pegawai ini sudah ditugaskan di survei ini.',
        ]);

        $pegawai = User::query()->findOrFail($data['user_id']);
        abort_unless($pegawai->hasRole('pegawai_bps'), 422, 'Hanya pegawai BPS yang dapat ditugaskan sebagai penanggung jawab survei.');

        $survey->pegawai()->attach($pegawai->id);
        $pegawai->notify(new PegawaiAssignedNotification($survey));

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => 'admin.survey.pegawai_assign',
            'description' => "Menugaskan {$pegawai->name} sebagai penanggung jawab survei {$survey->title}",
        ]);

        return back()->with('status', $pegawai->name.' ditugaskan sebagai penanggung jawab survei.');
    }

    public function destroy(Request $request, Survey $survey, User $user): RedirectResponse
    {
        $this->ensureSurveyIsEditable($survey);
        abort_unless($survey->pegawai()->whereKey($user->id)->exists(), 404);

        $survey->pegawai()->detach($user->id);

        ActivityLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => 'admin.survey.pegawai_unassign',
            'description' => "Mencabut {$user->name} dari penanggung jawab survei {$survey->title}",
        ]);

        return back()->with('status', $user->name.' tidak lagi menjadi penanggung jawab survei.');
    }

    private function ensureSurveyIsEditable(Survey $survey): void
    {
        abort_if($survey->status === 'Selesai', 403, 'Survei selesai tidak dapat diedit.');
    }
}
