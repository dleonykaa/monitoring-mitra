<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyEntry extends Model
{
    /**
     * Ruta sudah dialokasikan namun belum disentuh mitra.
     */
    public const STATUS_OPEN = 'open';

    /**
     * Isian disimpan sementara karena belum lengkap.
     */
    public const STATUS_DRAFT = 'draft';

    /**
     * Seluruh isian dan foto bukti pencacahan sudah lengkap (ditampilkan sebagai "Selesai").
     */
    public const STATUS_SUBMITTED = 'submitted';

    /**
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        self::STATUS_OPEN => 'Open',
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_SUBMITTED => 'Selesai',
    ];

    protected $fillable = [
        'survey_id',
        'survey_assignment_id',
        'district_id',
        'village_id',
        'kode_nks',
        'sls',
        'ppl',
        'no_urut_ruta',
        'evidence_photo_path',
        'entry_status',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    public function isSubmitted(): bool
    {
        return $this->entry_status === self::STATUS_SUBMITTED;
    }

    /**
     * Entri Open dan Draft masih bisa diisi mitra; entri Selesai terkunci.
     */
    public function isEditableByMitra(): bool
    {
        return ! $this->isSubmitted();
    }

    /**
     * Ruta dari import alokasi sudah membawa wilayah, SLS, dan nomor urut dari admin.
     */
    public function hasAllocatedIdentity(): bool
    {
        return $this->exists && filled($this->village_id) && filled($this->sls) && filled($this->no_urut_ruta);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->entry_status] ?? ucfirst((string) $this->entry_status);
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
}
