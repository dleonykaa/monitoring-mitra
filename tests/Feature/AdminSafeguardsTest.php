<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSafeguardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleted_mitra_keeps_allocation_history_readable(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@bps.go.id')->firstOrFail();
        $assignment = SurveyAssignment::query()
            ->whereHas('entries', fn ($query) => $query->where('entry_status', SurveyEntry::STATUS_SUBMITTED))
            ->with('mitra')
            ->firstOrFail();
        $entry = $assignment->entries()->where('entry_status', SurveyEntry::STATUS_SUBMITTED)->firstOrFail();
        $mitraName = $assignment->mitra->name;

        $this->actingAs($admin)->delete('/admin/users/'.$assignment->mitra_id)->assertSessionHas('status');
        $this->assertSoftDeleted('users', ['id' => $assignment->mitra_id]);

        // Halaman yang menampilkan riwayat mitra tetap terbuka dan masih menyebut namanya.
        $this->actingAs($admin)->get('/admin/surveys/'.$assignment->survey_id.'/assignments')->assertOk()->assertSee($mitraName);
        $this->actingAs($admin)->get('/admin/surveys/'.$assignment->survey_id)->assertOk();
        $this->actingAs($admin)->get('/admin/entri-papi/'.$entry->id)->assertOk()->assertSee($mitraName);
    }

    public function test_allocation_with_filled_ruta_cannot_be_deleted(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@bps.go.id')->firstOrFail();
        $assignment = SurveyAssignment::query()
            ->whereHas('survey', fn ($query) => $query->where('status', 'Berjalan')->where('type', Survey::TYPE_PAPI))
            ->whereHas('entries', fn ($query) => $query->where('entry_status', SurveyEntry::STATUS_SUBMITTED))
            ->firstOrFail();
        $entryCount = $assignment->entries()->count();

        $this->actingAs($admin)->from('/admin/surveys/'.$assignment->survey_id.'/assignments')
            ->delete('/admin/surveys/'.$assignment->survey_id.'/assignments/'.$assignment->id)
            ->assertSessionHasErrors('mitra');
        $this->assertModelExists($assignment);
        $this->assertSame($entryCount, $assignment->entries()->count());

        // Alokasi yang semua rutanya masih Open tetap bisa dihapus.
        $assignment->entries()->update(['entry_status' => SurveyEntry::STATUS_OPEN]);
        $this->actingAs($admin)
            ->delete('/admin/surveys/'.$assignment->survey_id.'/assignments/'.$assignment->id)
            ->assertSessionHasNoErrors();
        $this->assertModelMissing($assignment);
    }

    public function test_admin_cannot_lock_themselves_out(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@bps.go.id')->firstOrFail();
        $payload = ['name' => $admin->name, 'email' => $admin->email, 'phone' => $admin->phone, 'is_active' => 1, 'role' => 'admin'];

        $this->actingAs($admin)->delete('/admin/users/'.$admin->id)->assertSessionHasErrors('role');
        $this->actingAs($admin)->put('/admin/users/'.$admin->id, [...$payload, 'is_active' => 0])->assertSessionHasErrors('role');
        $this->actingAs($admin)->put('/admin/users/'.$admin->id, [...$payload, 'role' => 'mitra'])->assertSessionHasErrors('role');
        $this->assertNotSoftDeleted($admin);
        $this->assertTrue($admin->fresh()->is_active);
        $this->assertTrue($admin->fresh()->hasRole('admin'));

        // Mengubah data diri sendiri tanpa menurunkan hak akses tetap bisa.
        $this->actingAs($admin)->put('/admin/users/'.$admin->id, [...$payload, 'name' => 'Admin Utama'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');
        $this->assertSame('Admin Utama', $admin->fresh()->name);

        // Admin lain boleh dihapus selama masih ada admin aktif, tetapi admin tidak bisa menghapus dirinya sendiri.
        $second = User::factory()->create(['is_active' => true]);
        $second->assignRole('admin');
        $this->actingAs($second)->delete('/admin/users/'.$admin->id)->assertSessionHas('status');
        $this->actingAs($second)->delete('/admin/users/'.$second->id)->assertSessionHasErrors('role');
    }
}
