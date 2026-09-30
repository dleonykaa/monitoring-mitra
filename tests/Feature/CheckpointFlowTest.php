<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\User;
use App\Notifications\CheckpointMissedNotification;
use Database\Seeders\RegionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckpointFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_manages_checkpoints_within_survey_period(): void
    {
        [$admin] = $this->users(1);
        $survey = $this->survey($admin);
        $url = '/admin/surveys/'.$survey->id.'/checkpoints';
        $date = now()->addDays(3)->toDateString();

        $this->actingAs($admin)->get($url)->assertOk()->assertSee('Belum ada checkpoint');

        $this->actingAs($admin)->from($url)->post($url, ['checkpoint_date' => $date, 'target_percentage' => 50])
            ->assertRedirect($url)
            ->assertSessionHasNoErrors();
        $this->assertSame(50, $survey->checkpoints()->sole()->target_percentage);

        // Tanggal yang sama memperbarui target, bukan menambah baris.
        $this->actingAs($admin)->post($url, ['checkpoint_date' => $date, 'target_percentage' => 60]);
        $this->assertSame(60, $survey->checkpoints()->sole()->target_percentage);

        // Di luar periode survei atau di luar 1–100% ditolak.
        $this->actingAs($admin)->post($url, ['checkpoint_date' => now()->addYear()->toDateString(), 'target_percentage' => 50])
            ->assertSessionHasErrors('checkpoint_date');
        $this->actingAs($admin)->post($url, ['checkpoint_date' => $date, 'target_percentage' => 120])
            ->assertSessionHasErrors('target_percentage');
        $this->assertSame(1, $survey->checkpoints()->count());

        $this->actingAs($admin)->get($url)->assertOk()->assertSee('60%')->assertSee('Mendatang');

        $this->actingAs($admin)->delete($url.'/'.$survey->checkpoints()->sole()->id)->assertSessionHasNoErrors();
        $this->assertSame(0, $survey->checkpoints()->count());
    }

    public function test_only_mitra_below_a_passed_checkpoint_are_alerted_once(): void
    {
        [$admin, $behind, $onTrack, $inactive] = $this->users(4);
        $inactive->update(['is_active' => false]);
        $survey = $this->survey($admin);
        $survey->assignments()->create(['mitra_id' => $behind->id, 'target' => 10, 'current_progress' => 3]);
        $survey->assignments()->create(['mitra_id' => $onTrack->id, 'target' => 10, 'current_progress' => 6]);
        $survey->assignments()->create(['mitra_id' => $inactive->id, 'target' => 10, 'current_progress' => 0]);
        $older = $survey->checkpoints()->create(['checkpoint_date' => now()->subDays(4)->toDateString(), 'target_percentage' => 40]);
        $passed = $survey->checkpoints()->create(['checkpoint_date' => now()->subDay()->toDateString(), 'target_percentage' => 50]);
        $upcoming = $survey->checkpoints()->create(['checkpoint_date' => now()->addDays(2)->toDateString(), 'target_percentage' => 90]);

        $this->artisan('app:send-checkpoint-alerts')->assertSuccessful();

        $notification = $behind->notifications()->sole();
        $this->assertSame(CheckpointMissedNotification::class, $notification->type);
        $this->assertStringContainsString('baru 30%', $notification->data['message']);
        $this->assertStringContainsString('target 50%', $notification->data['message']);
        $this->assertSame(0, $onTrack->notifications()->count());
        $this->assertSame(0, $inactive->notifications()->count());
        // Dua checkpoint terlewat sekaligus hanya menghasilkan satu peringatan (yang terbaru).
        $this->assertNotNull($older->fresh()->notified_at);
        $this->assertNotNull($passed->fresh()->notified_at);
        $this->assertNull($upcoming->fresh()->notified_at);

        // Menjalankan ulang tidak mengirim peringatan kedua untuk checkpoint yang sama.
        $this->artisan('app:send-checkpoint-alerts')->assertSuccessful();
        $this->assertSame(1, $behind->notifications()->count());
    }

    public function test_checkpoint_status_shows_for_mitra_and_in_monitoring(): void
    {
        [$admin, $behind, $onTrack] = $this->users(3);
        $survey = $this->survey($admin);
        $survey->assignments()->create(['mitra_id' => $behind->id, 'target' => 10, 'current_progress' => 3]);
        $survey->assignments()->create(['mitra_id' => $onTrack->id, 'target' => 10, 'current_progress' => 6]);
        $survey->recalculateTarget();
        $survey->checkpoints()->create(['checkpoint_date' => now()->subDay()->toDateString(), 'target_percentage' => 50]);
        $survey->checkpoints()->create(['checkpoint_date' => now()->addDays(2)->toDateString(), 'target_percentage' => 90]);

        // Mitra yang tertinggal melihat peringatan dan kekurangannya; keduanya melihat target berikutnya.
        $this->actingAs($behind)->get('/mitra/surveys/'.$survey->id)
            ->assertOk()
            ->assertSee('di bawah target')
            ->assertSee('Kurang 2 ruta')
            ->assertSeeInOrder(['Target berikutnya', '90%', '9 dari 10 ruta']);
        $this->actingAs($onTrack)->get('/mitra/surveys/'.$survey->id)
            ->assertOk()
            ->assertDontSee('di bawah target')
            ->assertSee('Target berikutnya');

        // Monitoring: progres survei 45% di bawah target 50%, dan hanya mitra yang tertinggal masuk kartu Peringatan.
        $this->actingAs($admin)->get('/admin/monitoring/progres?survey='.$survey->id)
            ->assertOk()
            ->assertSee('Di bawah target')
            ->assertSeeInOrder(['Peringatan', 'Survei di bawah target checkpoint', $behind->name, 'Capaian 30,0%'])
            ->assertDontSee('Capaian 60,0%');
    }

    public function test_capi_checkpoint_alerts_use_fasih_progress(): void
    {
        [$admin, $behind, $onTrack] = $this->users(3);
        $this->seed(RegionSeeder::class);
        $survey = Survey::query()->create([
            'type' => Survey::TYPE_CAPI,
            'title' => 'Survei CAPI Checkpoint',
            'start_date' => now()->subWeek()->toDateString(),
            'end_date' => now()->addWeek()->toDateString(),
            'status' => 'Berjalan',
            'created_by' => $admin->id,
            'total_target' => 0,
        ]);
        $import = $survey->fasihImports()->create(['user_id' => $admin->id, 'file_name' => 'uji.csv', 'row_count' => 3]);
        $row = fn (User $user, string $sls, int $total, int $submit): array => [
            'user_id' => $user->id, 'email' => strtolower($user->email), 'username' => $user->email,
            'region_code' => '3101010001'.$sls.'00', 'district_code' => '3101010', 'village_code' => '3101010001', 'sls_code' => '3101010001'.$sls,
            'total_region' => $total, 'open_count' => $total - $submit, 'draft_count' => 0, 'submit_count' => $submit, 'other_count' => 0,
            'status_breakdown' => [],
        ];
        $import->rows()->create($row($behind, '0001', 10, 2));
        $import->rows()->create($row($behind, '0002', 10, 2));
        $import->rows()->create($row($onTrack, '0003', 10, 9));
        $checkpoint = $survey->checkpoints()->create(['checkpoint_date' => now()->subDay()->toDateString(), 'target_percentage' => 50]);

        // Halaman checkpoint terbuka untuk survei CAPI dan menghitung mitra dari data FASIH.
        $this->actingAs($admin)->get('/admin/surveys/'.$survey->id.'/checkpoints')
            ->assertOk()
            ->assertSee('dari 2 mitra')
            ->assertSee('data FASIH terbaru');

        $this->artisan('app:send-checkpoint-alerts')->assertSuccessful();

        $notification = $behind->notifications()->sole();
        $this->assertStringContainsString('baru 20% (4 dari 20 dokumen)', $notification->data['message']);
        $this->assertArrayNotHasKey('survey_id', $notification->data);
        $this->assertSame(0, $onTrack->notifications()->count());
        $this->assertNotNull($checkpoint->fresh()->notified_at);

        $this->actingAs($admin)->get('/admin/monitoring/progres?survey='.$survey->id)
            ->assertOk()
            ->assertSeeInOrder(['Peringatan', $behind->name, '20,0%']);
    }

    /**
     * @return list<User>
     */
    private function users(int $count): array
    {
        $this->seed(RolePermissionSeeder::class);

        return collect(range(1, $count))->map(function (int $index): User {
            $user = User::factory()->create(['is_active' => true]);
            $user->assignRole($index === 1 ? 'admin' : 'mitra');

            return $user;
        })->all();
    }

    private function survey(User $admin): Survey
    {
        return Survey::query()->create([
            'type' => Survey::TYPE_PAPI,
            'title' => 'Survei Uji Checkpoint',
            'start_date' => now()->subWeek()->toDateString(),
            'end_date' => now()->addWeek()->toDateString(),
            'status' => 'Berjalan',
            'created_by' => $admin->id,
            'total_target' => 0,
        ]);
    }
}
