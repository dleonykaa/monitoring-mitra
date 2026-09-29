<?php

namespace App\Notifications;

use App\Models\SurveyEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class EntryInvalidNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly SurveyEntry $entry)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $survey = $this->entry->survey;

        return new DatabaseMessage([
            'title' => 'Entri Tidak Valid',
            'message' => 'Entri #'.$this->entry->id.' pada survei '.$survey->title.' tidak valid. Perbaiki kuesioner fisik sebelum dikumpulkan. Catatan: '.$this->entry->note,
            'entry_id' => $this->entry->id,
            'survey_id' => $survey->id,
            'url' => '/mitra/entries?survey_id='.$survey->id,
        ]);
    }
}
