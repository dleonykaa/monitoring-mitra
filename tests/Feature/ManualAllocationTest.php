<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyEntry;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualAllocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_allocation_creates_numbered_open_ruta_like_the_excel_import(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@bps.go.id')->firstOrFail();
        $mitra = User::factory()->create(['is_active' => true, 'name' => 'Uswatun Khasanah']);
        $mitra->assignRole('mitra');
        $village = Village::query()->with('district')->where('name', 'Pulau Tidung')->firstOrFail();
        $survey = Survey::query()->create([
            'type' => Survey::TYPE_PAPI,
            'title' => 'Survei Alokasi Manual',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'Draft',
            'created_by' => $admin->id,
            'total_target' => 0,
        ]);
        $url = '/admin/surveys/'.$survey->id.'/assignments';
        $payload = ['mitra_id' => $mitra->id, 'village_id' => $village->id, 'sls' => 'RT 002 RW 001'];

        $this->actingAs($admin)->get($url)
            ->assertOk()
            ->assertSee('name="village_id"', false)
            ->assertSee('name="sls"', false)
            ->assertSee('name="ppl"', false);

        // Target 10 menghasilkan 10 ruta Open bernomor 1–10; PPL kosong diisi nama mitra.
        $this->actingAs($admin)->from($url)->post($url, [...$payload, 'target' => 10])
            ->assertRedirect($url)
            ->assertSessionHasNoErrors();

        $entries = $survey->entries()->orderByRaw('cast(no_urut_ruta as unsigned)')->get();
        $this->assertSame(range(1, 10), $entries->map(fn (SurveyEntry $entry): int => (int) $entry->no_urut_ruta)->all());
        $this->assertSame([SurveyEntry::STATUS_OPEN], $entries->pluck('entry_status')->unique()->values()->all());
        $this->assertSame(['RT 002 RW 001'], $entries->pluck('sls')->unique()->values()->all());
        $this->assertSame(['Uswatun Khasanah'], $entries->pluck('ppl')->unique()->values()->all());
        $this->assertSame($village->district_id, $entries->first()->district_id);
        $this->assertSame(10, (int) $survey->assignments()->sole()->target);
        $this->assertSame(10, (int) $survey->fresh()->total_target);

        // Menambah lagi di SLS yang sama melanjutkan nomor terakhir dan menambah target mitra.
        $this->actingAs($admin)->post($url, [...$payload, 'sls' => 'rt 002  rw 001', 'ppl' => 'Petugas Lain', 'target' => 3])
            ->assertSessionHasNoErrors();
        $this->assertSame(13, $survey->entries()->count());
        $this->assertSame(13, (int) $survey->assignments()->sole()->target);
        $this->assertSame('Petugas Lain', $survey->entries()->where('no_urut_ruta', '13')->sole()->ppl);

        // SLS lain mulai lagi dari nomor 1.
        $this->actingAs($admin)->post($url, [...$payload, 'sls' => 'RT 003 RW 001', 'target' => 2])->assertSessionHasNoErrors();
        $this->assertSame(['1', '2'], $survey->entries()->where('sls', 'RT 003 RW 001')->orderBy('id')->pluck('no_urut_ruta')->all());

        // Nomor urut hanya sampai 99.
        $this->actingAs($admin)->post($url, [...$payload, 'target' => 90])->assertSessionHasErrors('target');
        $this->actingAs($admin)->post($url, [...$payload, 'target' => 100])->assertSessionHasErrors('target');
        $this->actingAs($admin)->post($url, ['mitra_id' => $mitra->id, 'target' => 5])->assertSessionHasErrors(['village_id', 'sls']);
        $this->assertSame(15, $survey->entries()->count());
    }

    public function test_admin_deletes_a_draft_survey_from_the_survey_list(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@bps.go.id')->firstOrFail();
        $pegawai = User::query()->where('email', 'pegawai@bps.go.id')->firstOrFail();
        $running = Survey::query()->where('status', 'Berjalan')->firstOrFail();
        $draft = Survey::query()->create([
            'type' => Survey::TYPE_PAPI,
            'title' => 'Survei Draft Dihapus',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'Draft',
            'created_by' => $admin->id,
            'total_target' => 0,
        ]);
        $draft->assignments()->create(['mitra_id' => User::query()->role('mitra')->firstOrFail()->id, 'target' => 2]);

        // Tombol hapus hanya ada pada baris survei Draft, dan hanya untuk admin.
        $this->actingAs($admin)->get('/admin/surveys?status=all')
            ->assertOk()
            ->assertSee('aria-label="Hapus survei Survei Draft Dihapus"', false)
            ->assertDontSee('aria-label="Hapus survei '.$running->title.'"', false);
        $this->actingAs($pegawai)->get('/pegawai/surveys')->assertOk()->assertDontSee('Hapus survei');
        $this->actingAs($pegawai)->delete('/admin/surveys/'.$draft->id)->assertForbidden();

        $this->actingAs($admin)->delete('/admin/surveys/'.$draft->id)
            ->assertRedirect('/admin/surveys')
            ->assertSessionHas('status', 'Survei berhasil dihapus.');
        $this->assertNull(Survey::query()->find($draft->id));
        $this->assertSame(0, SurveyAssignment::query()->where('survey_id', $draft->id)->count());
    }
}
