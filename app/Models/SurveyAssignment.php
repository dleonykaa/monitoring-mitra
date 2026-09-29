<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyAssignment extends Model
{
    protected $fillable = ['survey_id', 'mitra_id', 'target', 'current_progress'];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mitra_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(SurveyEntry::class);
    }
}
