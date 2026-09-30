<?php

namespace App\Notifications;

use App\Models\Survey;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class DeadlineReminderNotification extends Notification
{
    public function __construct(private readonly Survey $survey) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => 'Pengingat Deadline Survei',
            'message' => 'Survei '.$this->survey->title.' akan berakhir 3 hari lagi.',
            'survey_id' => $this->survey->id,
        ]);
    }
}
