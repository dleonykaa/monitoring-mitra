<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\DailyProgressReminderNotification;
use Illuminate\Console\Command;

class SendDailyProgressReminderCommand extends Command
{
    protected $signature = 'app:send-daily-progress-reminder';

    protected $description = 'Send daily progress reminders to mitra users';

    public function handle(): int
    {
        User::query()->role('mitra')->where('is_active', true)->chunkById(100, function ($users): void {
            foreach ($users as $user) {
                $user->notify(new DailyProgressReminderNotification());
            }
        });

        return self::SUCCESS;
    }
}
