<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntryVariableValue extends Model
{
    protected $fillable = ['survey_entry_id', 'survey_variable_id', 'value'];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(SurveyEntry::class, 'survey_entry_id');
    }

    public function variable(): BelongsTo
    {
        return $this->belongsTo(SurveyVariable::class, 'survey_variable_id');
    }
}
