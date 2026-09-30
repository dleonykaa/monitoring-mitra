<?php

namespace App\Console\Commands;

use App\Models\Survey;
use App\Notifications\DeadlineReminderNotification;
use Illuminate\Console\Command;

class SendDeadlineReminderCommand extends Command
{
    protected $signature = 'app:send-deadline-reminder';

    protected $description = 'Kirim pengingat H-3 ke mitra yang belum mencapai target pada survei berjalan';

    public function handle(): int
    {
        $surveys = Survey::query()
            ->where('status', 'Berjalan')
            ->whereDate('end_date', now()->addDays(3)->toDateString())
            ->with('assignments.mitra')
            ->get();

        foreach ($surveys as $survey) {
            foreach ($survey->assignments as $assignment) {
                if ($assignment->current_progress < $assignment->target && $assignment->mitra?->is_active) {
                    $assignment->mitra->notify(new DeadlineReminderNotification($survey));
                }
            }
        }

        return self::SUCCESS;
    }
}
