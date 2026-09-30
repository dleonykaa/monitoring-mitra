<?php

namespace App\Console\Commands;

use App\Models\SurveyAssignment;
use App\Models\SurveyCheckpoint;
use App\Notifications\CheckpointMissedNotification;
use Illuminate\Console\Command;

class SendCheckpointAlertsCommand extends Command
{
    protected $signature = 'app:send-checkpoint-alerts';

    protected $description = 'Kirim peringatan ke mitra yang capaiannya di bawah target checkpoint yang tanggalnya sudah lewat';

    public function handle(): int
    {
        $checkpoints = SurveyCheckpoint::query()
            ->whereNull('notified_at')
            ->whereDate('checkpoint_date', '<', now('Asia/Jakarta')->toDateString())
            ->whereHas('survey', fn ($query) => $query->where('status', 'Berjalan'))
            ->with('survey.assignments.mitra')
            ->orderBy('checkpoint_date')
            ->get();

        // Bila beberapa checkpoint satu survei terlewat sekaligus, mitra cukup diperingatkan untuk yang terbaru.
        foreach ($checkpoints->groupBy('survey_id') as $surveyCheckpoints) {
            $checkpoint = $surveyCheckpoints->last();

            $checkpoint->survey->assignments
                ->filter(fn (SurveyAssignment $assignment): bool => $assignment->isBelow($checkpoint) && (bool) $assignment->mitra?->is_active)
                ->each(fn (SurveyAssignment $assignment) => $assignment->mitra->notify(new CheckpointMissedNotification($checkpoint, $assignment)));

            SurveyCheckpoint::query()->whereKey($surveyCheckpoints->modelKeys())->update(['notified_at' => now()]);
        }

        return self::SUCCESS;
    }
}
