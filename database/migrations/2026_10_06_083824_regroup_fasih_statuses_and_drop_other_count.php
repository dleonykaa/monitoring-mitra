<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengelompokan status saat migration ini dibuat (salinan config/fasih.php).
     *
     * @var array<string, list<string>>
     */
    private const NEW_GROUPS = [
        'open' => ['OPEN'],
        'draft' => ['DRAFT', 'REJECTED BY PENGAWAS'],
        'submit' => [
            'SUBMITTED BY PENCACAH', 'APPROVED BY PENGAWAS', 'EDITED BY ADMIN KABUPATEN',
            'EDITED BY PENGAWAS', 'REVOKED BY PENGAWAS', 'SUBMITTED RESPONDENT',
        ],
    ];

    /**
     * @var array<string, list<string>>
     */
    private const OLD_GROUPS = [
        'open' => ['OPEN', 'REJECTED BY PENGAWAS'],
        'draft' => ['DRAFT'],
        'submit' => ['SUBMITTED BY PENCACAH', 'APPROVED BY PENGAWAS'],
    ];

    /**
     * Kelompok "Lainnya" dihapus: setiap status FASIH masuk Open, Draft, atau Submit sehingga
     * jumlahnya sama dengan total_region. Data lama dihitung ulang dari status_breakdown.
     */
    public function up(): void
    {
        $this->regroup(self::NEW_GROUPS, withOther: false);

        Schema::table('fasih_progress_rows', function (Blueprint $table): void {
            $table->dropColumn('other_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fasih_progress_rows', function (Blueprint $table): void {
            $table->unsignedInteger('other_count')->default(0)->after('submit_count');
        });

        $this->regroup(self::OLD_GROUPS, withOther: true);
    }

    /**
     * Status yang tidak dikenal pada data lama dimasukkan ke Open (sisa) agar total tetap utuh.
     *
     * @param  array<string, list<string>>  $groups
     */
    private function regroup(array $groups, bool $withOther): void
    {
        DB::table('fasih_progress_rows')->select(['id', 'total_region', 'status_breakdown'])->orderBy('id')
            ->chunkById(500, function ($rows) use ($groups, $withOther): void {
                foreach ($rows as $row) {
                    $counts = ['open' => 0, 'draft' => 0, 'submit' => 0];
                    foreach ((array) json_decode((string) $row->status_breakdown, true) as $status => $count) {
                        foreach ($groups as $group => $statuses) {
                            if (in_array(strtoupper((string) $status), $statuses, true)) {
                                $counts[$group] += (int) $count;
                            }
                        }
                    }

                    $remainder = max(0, (int) $row->total_region - array_sum($counts));
                    $update = [
                        'open_count' => $counts['open'] + ($withOther ? 0 : $remainder),
                        'draft_count' => $counts['draft'],
                        'submit_count' => $counts['submit'],
                    ];
                    if ($withOther) {
                        $update['other_count'] = $remainder;
                    }

                    DB::table('fasih_progress_rows')->where('id', $row->id)->update($update);
                }
            });
    }
};
