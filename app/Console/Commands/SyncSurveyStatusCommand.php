<?php

namespace App\Console\Commands;

use App\Models\Survey;
use Illuminate\Console\Command;

class SyncSurveyStatusCommand extends Command
{
    protected $signature = 'app:sync-survey-status';

    protected $description = 'Sync survey status based on current progress and targets';

    public function handle(): int
    {
        Survey::query()->with('assignments')->chunkById(100, function ($surveys): void {
            foreach ($surveys as $survey) {
                if (in_array($survey->status, ['Draft', 'Selesai'], true)) {
                    continue;
                }

                $progress = (int) $survey->assignments->sum('current_progress');
                $status = $progress >= $survey->total_target ? 'Selesai' : 'Berjalan';
                if ($survey->status !== $status) {
                    $survey->update(['status' => $status]);
                }
            }
        });

        return self::SUCCESS;
    }
}
