<?php

namespace App\Notifications;

use App\Models\Survey;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class MitraAssignedNotification extends Notification
{
    public function __construct(private readonly Survey $survey) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => 'Penugasan Survei Baru',
            'message' => 'Anda dialokasikan ke survei '.$this->survey->title.'.',
            'survey_id' => $this->survey->id,
        ]);
    }
}
