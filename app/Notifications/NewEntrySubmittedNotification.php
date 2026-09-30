<?php

namespace App\Notifications;

use App\Models\Survey;
use App\Models\SurveyEntry;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class NewEntrySubmittedNotification extends Notification
{
    public function __construct(private readonly Survey $survey, private readonly SurveyEntry $entry) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => 'Update Progres Mitra',
            'message' => ($this->entry->ppl ?: 'Mitra').' menyelesaikan entri ruta '.($this->entry->no_urut_ruta ?: '-').' pada survei '.$this->survey->title.'.',
            'entry_id' => $this->entry->id,
            'survey_id' => $this->survey->id,
        ]);
    }
}
