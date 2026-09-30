<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyAssignment extends Model
{
    protected $fillable = ['survey_id', 'mitra_id', 'target', 'current_progress'];

    /**
     * Persentase ruta selesai terhadap target mitra ini (0–100, satu desimal).
     */
    public function progressPercent(): float
    {
        return $this->target > 0 ? round(min(100, $this->current_progress / $this->target * 100), 1) : 0.0;
    }

    /**
     * Capaian mitra masih di bawah persentase target checkpoint.
     */
    public function isBelow(SurveyCheckpoint $checkpoint): bool
    {
        return $this->target > 0 && $this->progressPercent() < $checkpoint->target_percentage;
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    /**
     * Akun mitra yang dihapus admin tetap terbaca agar riwayat alokasi dan entrinya utuh.
     */
    public function mitra(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mitra_id')->withTrashed();
    }

    public function entries(): HasMany
    {
        return $this->hasMany(SurveyEntry::class);
    }
}
