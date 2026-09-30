<?php

namespace App\Console\Commands;

use App\Models\Survey;
use Illuminate\Console\Command;

/**
 * Menjaga total target survei berjalan tetap sesuai alokasi (PAPI) atau import FASIH terbaru (CAPI).
 * Status survei tidak diubah: survei hanya menjadi Selesai bila admin menandainya.
 */
class SyncSurveyStatusCommand extends Command
{
    protected $signature = 'app:sync-survey-status';

    protected $description = 'Sinkronkan total target survei berjalan tanpa mengubah statusnya';

    public function handle(): int
    {
        Survey::query()
            ->where('status', 'Berjalan')
            ->chunkById(100, fn ($surveys) => $surveys->each(fn (Survey $survey) => $survey->recalculateTarget()));

        return self::SUCCESS;
    }
}
