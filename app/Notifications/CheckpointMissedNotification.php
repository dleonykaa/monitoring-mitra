<?php

namespace App\Notifications;

use App\Models\SurveyAssignment;
use App\Models\SurveyCheckpoint;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class CheckpointMissedNotification extends Notification
{
    public function __construct(
        private readonly SurveyCheckpoint $checkpoint,
        private readonly SurveyAssignment $assignment,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $survey = $this->checkpoint->survey;

        return new DatabaseMessage([
            'title' => 'Capaian di Bawah Target',
            'message' => 'Capaian Anda pada survei '.$survey->title.' baru '.$this->assignment->progressPercent().'% ('
                .$this->assignment->current_progress.' dari '.$this->assignment->target.' ruta), di bawah target '
                .$this->checkpoint->target_percentage.'% per '.$this->checkpoint->checkpoint_date->locale('id')->translatedFormat('d M Y').'.',
            'survey_id' => $survey->id,
        ]);
    }
}
