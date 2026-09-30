<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\DailyProgressReminderNotification;
use Illuminate\Console\Command;

class SendDailyProgressReminderCommand extends Command
{
    protected $signature = 'app:send-daily-progress-reminder';

    protected $description = 'Kirim pengingat harian ke mitra yang masih punya alokasi belum tuntas di survei berjalan';

    public function handle(): int
    {
        User::query()
            ->role('mitra')
            ->where('is_active', true)
            ->whereHas('assignments', fn ($query) => $query
                ->whereColumn('current_progress', '<', 'target')
                ->whereHas('survey', fn ($survey) => $survey->where('status', 'Berjalan')))
            ->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    $user->notify(new DailyProgressReminderNotification);
                }
            });

        return self::SUCCESS;
    }
}
