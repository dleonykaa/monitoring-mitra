<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Survey extends Model
{
    public const TYPE_PAPI = 'papi';

    public const TYPE_CAPI = 'capi';

    /**
     * @var array<string, string>
     */
    public const TYPES = [
        self::TYPE_PAPI => 'PAPI',
        self::TYPE_CAPI => 'CAPI',
    ];

    protected $fillable = ['created_by', 'title', 'type', 'description', 'total_target', 'start_date', 'end_date', 'status'];

    protected $attributes = [
        'type' => self::TYPE_PAPI,
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function isCapi(): bool
    {
        return $this->type === self::TYPE_CAPI;
    }

    /**
     * Persentase waktu periode survei yang sudah berjalan sampai hari ini (0–100).
     */
    public function elapsedPercent(): float
    {
        $start = Carbon::parse($this->start_date->toDateString(), 'Asia/Jakarta');
        $end = Carbon::parse($this->end_date->toDateString(), 'Asia/Jakarta');
        $totalDays = max(1, (int) $start->diffInDays($end) + 1);
        $elapsedDays = (int) $start->diffInDays(now('Asia/Jakarta')->startOfDay(), false) + 1;

        return round(min(100, max(0, $elapsedDays / $totalDays * 100)), 1);
    }

    /**
     * Checkpoint terakhir yang tanggalnya sudah lewat: target yang seharusnya sudah tercapai sekarang.
     */
    public function passedCheckpoint(): ?SurveyCheckpoint
    {
        $today = now('Asia/Jakarta')->toDateString();

        return $this->checkpoints->last(fn (SurveyCheckpoint $checkpoint): bool => $checkpoint->checkpoint_date->toDateString() < $today);
    }

    /**
     * Checkpoint terdekat yang belum lewat (termasuk hari ini).
     */
    public function nextCheckpoint(): ?SurveyCheckpoint
    {
        $today = now('Asia/Jakarta')->toDateString();

        return $this->checkpoints->first(fn (SurveyCheckpoint $checkpoint): bool => $checkpoint->checkpoint_date->toDateString() >= $today);
    }

    /**
     * Survei berjalan dianggap tertinggal bila progresnya di bawah target checkpoint terakhir yang sudah lewat.
     * Tanpa checkpoint, patokannya porsi waktu yang sudah berjalan dengan toleransi 10 poin.
     */
    public function isBehindSchedule(float $progressPercent): bool
    {
        if ($this->status !== 'Berjalan' || $progressPercent >= 100) {
            return false;
        }

        $checkpoint = $this->passedCheckpoint();

        return $checkpoint
            ? $progressPercent < $checkpoint->target_percentage
            : $progressPercent + 10 < $this->elapsedPercent();
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? strtoupper((string) $this->type);
    }

    /**
     * Relasi yang dibutuhkan halaman detail survei (admin maupun pegawai).
     */
    public function loadDetail(): static
    {
        return $this->load([
            'variables',
            'assignments' => fn ($query) => $query->with('mitra')->orderByDesc('current_progress')->orderByDesc('target'),
            'fasihImports' => fn ($query) => $query->with('user')->latest('id'),
        ])->loadCount(['entries as submitted_entries_count' => fn ($query) => $query->where('entry_status', SurveyEntry::STATUS_SUBMITTED)]);
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

    public function fasihImports(): HasMany
    {
        return $this->hasMany(FasihImport::class);
    }

    public function latestFasihImport(): HasOne
    {
        return $this->hasOne(FasihImport::class)->latestOfMany();
    }

    /**
     * Total target = akumulasi target mitra (PAPI) atau total beban pada import FASIH terbaru (CAPI).
     */
    public function recalculateTarget(): void
    {
        $total = $this->isCapi()
            ? (int) FasihProgressRow::query()->where('fasih_import_id', $this->latestFasihImport()->value('id'))->sum('total_region')
            : (int) $this->assignments()->sum('target');

        if ((int) $this->total_target !== $total) {
            $this->update(['total_target' => $total]);
        }
    }
}
