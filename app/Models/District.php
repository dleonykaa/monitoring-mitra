<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    protected $fillable = ['name'];

    public function villages(): HasMany
    {
        return $this->hasMany(Village::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(SurveyEntry::class);
    }
}
