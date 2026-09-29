<?php

namespace App\Notifications;

use App\Models\Survey;
use App\Models\SurveyEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class NewEntrySubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Survey $survey, private readonly SurveyEntry $entry)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => 'Entri Baru Mitra',
            'message' => 'Entri baru diterima untuk survei '.$this->survey->title,
            'entry_id' => $this->entry->id,
            'survey_id' => $this->survey->id,
        ]);
    }
}
