<?php

namespace App\Console\Commands;

use App\Models\SurveyCheckpoint;
use App\Notifications\CheckpointMissedNotification;
use App\Services\MitraCheckpointProgress;
use Illuminate\Console\Command;

class SendCheckpointAlertsCommand extends Command
{
    protected $signature = 'app:send-checkpoint-alerts';

    protected $description = 'Kirim peringatan ke mitra yang capaiannya di bawah target checkpoint yang tanggalnya sudah lewat';

    public function handle(MitraCheckpointProgress $progress): int
    {
        $checkpoints = SurveyCheckpoint::query()
            ->whereNull('notified_at')
            ->whereDate('checkpoint_date', '<', now('Asia/Jakarta')->toDateString())
            ->whereHas('survey', fn ($query) => $query->where('status', 'Berjalan'))
            ->with('survey.latestFasihImport')
            ->orderBy('checkpoint_date')
            ->get();

        // Bila beberapa checkpoint satu survei terlewat sekaligus, mitra cukup diperingatkan untuk yang terbaru.
        foreach ($checkpoints->groupBy('survey_id') as $surveyCheckpoints) {
            $checkpoint = $surveyCheckpoints->last();

            // Mitra CAPI yang belum punya akun SIMPROCA tidak bisa diberi notifikasi.
            $progress->below($checkpoint->survey, $checkpoint)
                ->filter(fn (array $mitra): bool => $mitra['user'] && $mitra['user']->is_active && ! $mitra['user']->trashed())
                ->each(fn (array $mitra) => $mitra['user']->notify(new CheckpointMissedNotification($checkpoint, $mitra)));

            SurveyCheckpoint::query()->whereKey($surveyCheckpoints->modelKeys())->update(['notified_at' => now()]);
        }

        return self::SUCCESS;
    }
}
