<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Survey extends Model
{
    protected $fillable = ['team_id', 'created_by', 'title', 'description', 'total_target', 'start_date', 'end_date', 'status', 'validation_formula', 'validation_rules'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'validation_rules' => 'array',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function variables(): HasMany
    {
        return $this->hasMany(SurveyVariable::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(SurveyAssignment::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(SurveyEntry::class);
    }

    public function checkpoints(): HasMany
    {
        return $this->hasMany(SurveyCheckpoint::class)->orderBy('checkpoint_date');
    }
}
