<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyEntry extends Model
{
    protected $fillable = [
        'survey_id',
        'survey_assignment_id',
        'district_id',
        'village_id',
        'kode_nks',
        'sls',
        'ppl',
        'no_urut_ruta',
        'respondent_name',
        'evidence_photo_path',
        'is_valid',
        'validated_by',
        'note',
        'internal_note',
        'entry_status',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'is_valid' => 'boolean',
            'submitted_at' => 'datetime',
        ];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(SurveyAssignment::class, 'survey_assignment_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(EntryVariableValue::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
