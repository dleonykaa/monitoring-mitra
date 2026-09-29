<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyVariable extends Model
{
    protected $fillable = ['survey_id', 'name', 'data_type', 'example_format'];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }
}
