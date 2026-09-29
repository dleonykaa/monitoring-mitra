<?php

namespace App\Notifications;

use App\Models\Survey;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class MitraAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Survey $survey)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => 'Penugasan Survei Baru',
            'message' => 'Anda ditugaskan ke survei '.$this->survey->title,
            'survey_id' => $this->survey->id,
        ]);
    }
}
