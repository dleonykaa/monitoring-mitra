<?php

namespace App\Notifications;

use App\Models\SurveyCheckpoint;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class CheckpointMissedNotification extends Notification
{
    /**
     * @param  array{progress: int, target: int, percent: float}  $achievement
     */
    public function __construct(
        private readonly SurveyCheckpoint $checkpoint,
        private readonly array $achievement,
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
            'message' => 'Capaian Anda pada survei '.$survey->title.' baru '.$this->achievement['percent'].'% ('
                .$this->achievement['progress'].' dari '.$this->achievement['target'].' '.($survey->isCapi() ? 'dokumen' : 'ruta').'), di bawah target '
                .$this->checkpoint->target_percentage.'% per '.$this->checkpoint->checkpoint_date->locale('id')->translatedFormat('d M Y').'.',
            // Hanya survei PAPI yang punya halaman pengisian di panel mitra.
            ...($survey->isCapi() ? [] : ['survey_id' => $survey->id]),
        ]);
    }
}
