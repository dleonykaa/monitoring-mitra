<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyEntry;
use App\Models\SurveyVariable;
use App\Models\User;
use App\Models\Village;
use App\Notifications\MitraAssignedNotification;
use App\Notifications\NewEntrySubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_mitra_is_notified_when_allocated_to_a_running_survey(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@bps.go.id')->firstOrFail();
        $mitra = User::factory()->create(['is_active' => true]);
        $mitra->assignRole('mitra');
        $survey = $this->papiSurvey($admin, 'Berjalan');

        $this->actingAs($admin)
            ->post('/admin/surveys/'.$survey->id.'/assignments', ['mitra_id' => $mitra->id, 'village_id' => Village::query()->firstOrFail()->id, 'sls' => 'RT 001 RW 001', 'target' => 5])
            ->assertSessionHasNoErrors();

        $notification = $mitra->notifications()->sole();
        $this->assertSame(MitraAssignedNotification::class, $notification->type);
        $this->assertSame($survey->id, $notification->data['survey_id']);

        // Menambah ruta untuk mitra yang sama tidak mengirim notifikasi kedua.
        $this->actingAs($admin)
            ->post('/admin/surveys/'.$survey->id.'/assignments', ['mitra_id' => $mitra->id, 'village_id' => Village::query()->firstOrFail()->id, 'sls' => 'RT 001 RW 001', 'target' => 8]);
        $this->assertSame(1, $mitra->notifications()->count());

        $this->actingAs($mitra)->get('/mitra/dashboard')
            ->assertOk()
            ->assertSee('Penugasan Survei Baru')
            ->assertSee('/mitra/surveys/'.$survey->id, false);
    }

    public function test_mitra_allocated_to_draft_survey_is_notified_when_survey_starts(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@bps.go.id')->firstOrFail();
        $mitra = User::factory()->create(['is_active' => true]);
        $mitra->assignRole('mitra');
        $survey = $this->papiSurvey($admin, 'Draft');

        $this->actingAs($admin)
            ->post('/admin/surveys/'.$survey->id.'/assignments', ['mitra_id' => $mitra->id, 'village_id' => Village::query()->firstOrFail()->id, 'sls' => 'RT 001 RW 001', 'target' => 5]);
        $this->assertSame(0, $mitra->notifications()->count());

        $this->actingAs($admin)->post('/admin/surveys/'.$survey->id.'/finish-setup')->assertRedirect('/admin/surveys');
        $this->assertSame(MitraAssignedNotification::class, $mitra->notifications()->sole()->type);
    }

    public function test_admin_and_pegawai_are_notified_when_mitra_submits_an_entry(): void
    {
        $this->seed();
        Storage::fake('public');

        $admin = User::query()->where('email', 'admin@bps.go.id')->firstOrFail();
        $pegawai = User::query()->where('email', 'pegawai@bps.go.id')->firstOrFail();
        $mitra = User::query()->where('email', 'mitra@bps.go.id')->firstOrFail();
        $village = Village::query()->firstOrFail();

        $survey = $this->papiSurvey($admin, 'Berjalan');
        $variable = SurveyVariable::query()->create([
            'survey_id' => $survey->id,
            'name' => 'Jumlah ART',
            'data_type' => 'number',
            'example_format' => '4',
        ]);
        SurveyAssignment::query()->create(['survey_id' => $survey->id, 'mitra_id' => $mitra->id, 'target' => 3]);

        $entryPayload = [
            'district_id' => $village->district_id,
            'village_id' => $village->id,
            'sls' => '0001',
            'no_urut_ruta' => '7',
            'variables' => [$variable->id => '4'],
        ];

        // Draft belum dihitung sebagai update, jadi belum ada notifikasi.
        $this->actingAs($mitra)->post('/mitra/surveys/'.$survey->id.'/entries', [...$entryPayload, 'action' => 'draft']);
        $this->assertSame(0, $admin->notifications()->count());

        $entry = SurveyEntry::query()->where('survey_id', $survey->id)->sole();
        $this->actingAs($mitra)->put('/mitra/entries/'.$entry->id, [
            ...$entryPayload,
            'action' => 'submit',
            'evidence_photo' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertSessionHasNoErrors();

        foreach ([$admin, $pegawai] as $staff) {
            $notification = $staff->notifications()->sole();
            $this->assertSame(NewEntrySubmittedNotification::class, $notification->type);
            $this->assertSame($entry->id, $notification->data['entry_id']);
            $this->assertStringContainsString($survey->title, $notification->data['message']);
        }
        $this->assertSame(0, $mitra->notifications()->where('type', NewEntrySubmittedNotification::class)->count());

        // Tautan notifikasi mengikuti panel masing-masing peran.
        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Update Progres Mitra')
            ->assertSee('/admin/entri-papi/'.$entry->id, false);
        $this->actingAs($admin)->get('/admin/entri-papi/'.$entry->id)->assertOk();
        $this->actingAs($pegawai)->get('/pegawai/dashboard')
            ->assertOk()
            ->assertSee('/pegawai/entri-papi/'.$entry->id, false);
    }

    public function test_user_can_mark_notifications_read_and_clear_them(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@bps.go.id')->firstOrFail();
        $mitra = User::query()->where('email', 'mitra@bps.go.id')->firstOrFail();
        $survey = $this->papiSurvey($admin, 'Berjalan');
        $mitra->notify(new MitraAssignedNotification($survey));
        $admin->notify(new MitraAssignedNotification($survey));

        $this->assertSame(1, $mitra->unreadNotifications()->count());

        $this->actingAs($mitra)->postJson('/notifications/read')->assertOk()->assertJson(['unread' => 0]);
        $this->assertSame(0, $mitra->unreadNotifications()->count());
        $this->assertSame(1, $mitra->notifications()->count());

        $this->actingAs($mitra)->from('/mitra/dashboard')->delete('/notifications')->assertRedirect('/mitra/dashboard');
        $this->assertSame(0, $mitra->notifications()->count());

        // Notifikasi pengguna lain tidak ikut terhapus.
        $this->assertSame(1, $admin->notifications()->count());
    }

    private function papiSurvey(User $admin, string $status): Survey
    {
        return Survey::query()->create([
            'type' => Survey::TYPE_PAPI,
            'title' => 'Survei Uji Notifikasi',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'status' => $status,
            'created_by' => $admin->id,
            'total_target' => 0,
        ]);
    }
}
