<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FasihProgressRow extends Model
{
    protected $fillable = [
        'fasih_import_id', 'user_id', 'fasih_user_id', 'username', 'pencacah_name', 'email',
        'region_code', 'district_code', 'village_code', 'sls_code',
        'total_region', 'open_count', 'draft_count', 'submit_count', 'other_count',
        'status_breakdown',
    ];

    protected function casts(): array
    {
        return [
            'total_region' => 'integer',
            'open_count' => 'integer',
            'draft_count' => 'integer',
            'submit_count' => 'integer',
            'other_count' => 'integer',
            'status_breakdown' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
