<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyCheckpoint extends Model
{
    protected $fillable = ['survey_id', 'checkpoint_date', 'target_percentage', 'notified_at'];

    protected function casts(): array
    {
        return [
            'checkpoint_date' => 'date',
            'target_percentage' => 'integer',
            'notified_at' => 'datetime',
        ];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }
}
