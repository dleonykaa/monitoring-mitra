<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\User;
use App\Notifications\DailyProgressReminderNotification;
use App\Notifications\DeadlineReminderNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ScheduledCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_reminder_only_reaches_mitra_with_unfinished_running_assignments(): void
    {
        [$admin, $pending, $finished, $draftOnly, $idle] = $this->users(5);
        $running = $this->survey($admin, 'Survei Berjalan Pengingat', 'Berjalan', now()->addMonth());
        $draft = $this->survey($admin, 'Survei Draft Pengingat', 'Draft', now()->addMonth());
        $running->assignments()->create(['mitra_id' => $pending->id, 'target' => 5, 'current_progress' => 2]);
        $running->assignments()->create(['mitra_id' => $finished->id, 'target' => 5, 'current_progress' => 5]);
        $draft->assignments()->create(['mitra_id' => $draftOnly->id, 'target' => 5, 'current_progress' => 0]);

        Notification::fake();
        $this->artisan('app:send-daily-progress-reminder')->assertSuccessful();

        Notification::assertSentTo($pending, DailyProgressReminderNotification::class);
        Notification::assertNotSentTo([$finished, $draftOnly, $idle], DailyProgressReminderNotification::class);
    }

    public function test_deadline_reminder_ignores_surveys_that_are_not_running(): void
    {
        [$admin, $runningMitra, $draftMitra] = $this->users(3);
        $inThreeDays = now()->addDays(3);
        $this->survey($admin, 'Survei Berjalan H-3', 'Berjalan', $inThreeDays)
            ->assignments()->create(['mitra_id' => $runningMitra->id, 'target' => 5, 'current_progress' => 1]);
        $this->survey($admin, 'Survei Draft H-3', 'Draft', $inThreeDays)
            ->assignments()->create(['mitra_id' => $draftMitra->id, 'target' => 5, 'current_progress' => 1]);

        Notification::fake();
        $this->artisan('app:send-deadline-reminder')->assertSuccessful();

        Notification::assertSentTo($runningMitra, DeadlineReminderNotification::class);
        Notification::assertNotSentTo($draftMitra, DeadlineReminderNotification::class);
    }

    public function test_sync_command_keeps_status_and_only_recalculates_target(): void
    {
        [$admin, $mitra] = $this->users(2);
        $survey = $this->survey($admin, 'Survei Target Tercapai', 'Berjalan', now()->addWeek());
        $survey->assignments()->create(['mitra_id' => $mitra->id, 'target' => 4, 'current_progress' => 4]);

        $this->artisan('app:sync-survey-status')->assertSuccessful();

        $survey->refresh();
        $this->assertSame('Berjalan', $survey->status);
        $this->assertSame(4, (int) $survey->total_target);
    }

    /**
     * @return list<User>
     */
    private function users(int $count): array
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return [$admin, ...collect(range(2, $count))->map(function (): User {
            $mitra = User::factory()->create(['is_active' => true]);
            $mitra->assignRole('mitra');

            return $mitra;
        })->all()];
    }

    private function survey(User $admin, string $title, string $status, \DateTimeInterface $endDate): Survey
    {
        return Survey::query()->create([
            'type' => Survey::TYPE_PAPI,
            'title' => $title,
            'start_date' => now()->subWeek()->toDateString(),
            'end_date' => $endDate->format('Y-m-d'),
            'status' => $status,
            'created_by' => $admin->id,
            'total_target' => 0,
        ]);
    }
}
