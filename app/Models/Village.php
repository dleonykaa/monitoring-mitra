<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Village extends Model
{
    protected $fillable = ['district_id', 'code', 'name', 'type'];

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(SurveyEntry::class);
    }
}
