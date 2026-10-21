<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * Panel setiap peran: awalan alamat, judul panel, menu samping, dan label peran.
     *
     * @var array<string, array{base: string, panelTitle: string, menuView: string, label: string}>
     */
    public const PANELS = [
        'admin' => ['base' => '/admin', 'panelTitle' => 'Admin', 'menuView' => 'panel.admin.menu', 'label' => 'Administrator'],
        'pegawai_bps' => ['base' => '/pegawai', 'panelTitle' => 'Pegawai BPS', 'menuView' => 'panel.pegawai.menu', 'label' => 'Pegawai BPS'],
        'pimpinan' => ['base' => '/pimpinan', 'panelTitle' => 'Pimpinan', 'menuView' => 'panel.pimpinan.menu', 'label' => 'Pimpinan'],
        'mitra' => ['base' => '/mitra', 'panelTitle' => 'Mitra BPS', 'menuView' => 'panel.mitra.menu', 'label' => 'Mitra BPS'],
    ];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Panel milik peran akun ini; null bila akun belum punya peran.
     *
     * @return array{base: string, panelTitle: string, menuView: string, label: string}|null
     */
    public function panel(): ?array
    {
        return self::PANELS[$this->getRoleNames()->first()] ?? null;
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(SurveyAssignment::class, 'mitra_id');
    }

    /**
     * Survei yang ditanggungjawabi pegawai ini.
     */
    public function supervisedSurveys(): BelongsToMany
    {
        return $this->belongsToMany(Survey::class, 'survey_pegawai')->withTimestamps();
    }
}
