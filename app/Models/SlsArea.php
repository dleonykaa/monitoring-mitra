<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlsArea extends Model
{
    protected $fillable = ['village_id', 'code', 'name'];

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }
}
