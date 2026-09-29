<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyCheckpoint extends Model
{
    protected $fillable = ['survey_id', 'checkpoint_date', 'target_percentage'];

    protected function casts(): array
    {
        return [
            'checkpoint_date' => 'date',
            'target_percentage' => 'integer',
        ];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }
}
