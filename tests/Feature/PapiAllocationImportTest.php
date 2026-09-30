<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\SurveyEntry;
use App\Models\SurveyVariable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PapiAllocationImportTest extends TestCase
{
    use RefreshDatabase;

    private const HEADER = 'kode prop,kode kab,Kelurahan,Email,SLS,PPL,No Urut Ruta [max: 2 digit]';

    private User $admin;

    private Survey $survey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->admin = User::query()->where('email', 'admin@bps.go.id')->firstOrFail();
        $this->survey = Survey::query()->create([
            'type' => Survey::TYPE_PAPI,
            'title' => 'Survei Alokasi Uji',
            'created_by' => $this->admin->id,
            'total_target' => 0,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'Draft',
        ]);
    }

    public function test_admin_downloads_template_with_email_column(): void
    {
        $this->actingAs($this->admin)->get('/admin/surveys/'.$this->survey->id.'/assignments/template')
            ->assertOk()
            ->assertDownload('template-alokasi-survei-alokasi-uji.xlsx');

        $this->actingAs($this->admin)->get('/admin/surveys/'.$this->survey->id.'/assignments')
            ->assertOk()
            ->assertSee('Impor dari Excel')
            ->assertSee('Email mitra');
    }

    public function test_import_allocates_ruta_by_email_and_mitra_can_fill_them(): void
    {
        Storage::fake(SurveyEntry::PHOTO_DISK);
        $mitra = User::query()->where('email', 'mitra@bps.go.id')->firstOrFail();
        $other = User::query()->where('email', 'siti.nurhaliza@gmail.com')->firstOrFail();
        $variable = SurveyVariable::query()->create(['survey_id' => $this->survey->id, 'name' => 'Jumlah ART', 'data_type' => 'number', 'example_format' => '4']);

        $this->import([
            ',,,,,CONTOH PENGISIAN,',
            '31,1,PULAU TIDUNG,MITRA@BPS.GO.ID,RT 002 RW 001,Ahmad Fauzi,1',
            '31,01,Pulau Tidung,mitra@bps.go.id,RT 002 RW 001,Ahmad Fauzi,2',
            '31,01,PULAU PARI,siti.nurhaliza@gmail.com,RT 001 RW 002,Siti Nurhaliza,1',
        ])->assertSessionHasNoErrors()->assertSessionHas('status');

        $this->assertSame(2, (int) $this->survey->assignments()->where('mitra_id', $mitra->id)->value('target'));
        $this->assertSame(1, (int) $this->survey->assignments()->where('mitra_id', $other->id)->value('target'));
        $this->assertSame(3, (int) $this->survey->fresh()->total_target);

        $entry = SurveyEntry::query()->where('survey_id', $this->survey->id)->where('no_urut_ruta', '2')->sole();
        $this->assertSame(SurveyEntry::STATUS_OPEN, $entry->entry_status);
        $this->assertSame('RT 002 RW 001', $entry->sls);
        $this->assertSame('Pulau Tidung', $entry->village->name);
        $this->assertSame('Kepulauan Seribu Selatan', $entry->district->name);

        // Belum muncul di akun mitra sebelum survei dijalankan.
        $this->actingAs($mitra)->get('/mitra/surveys')->assertOk()->assertDontSee('Survei Alokasi Uji');

        $this->actingAs($this->admin)->post('/admin/surveys/'.$this->survey->id.'/finish-setup')->assertRedirect('/admin/surveys');

        $this->actingAs($mitra)->get('/mitra/dashboard')->assertOk()->assertSee('Survei Alokasi Uji');
        $this->actingAs($mitra)->get('/mitra/surveys/'.$this->survey->id)
            ->assertOk()
            ->assertSee('Ruta 1')
            ->assertSee('Ruta 2')
            ->assertDontSee('RT 001 RW 002');
        $this->actingAs($mitra)->get('/mitra/entries/'.$entry->id.'/edit')
            ->assertOk()
            ->assertSee('Ditetapkan admin saat alokasi.')
            ->assertSee('RT 002 RW 001')
            ->assertDontSee('Kode NKS')
            ->assertDontSee('name="sls"', false);

        // Mitra cukup mengisi variabel dan foto; identitas ruta tetap dari alokasi.
        $this->actingAs($mitra)->put('/mitra/entries/'.$entry->id, [
            'action' => 'submit',
            'sls' => 'DIUBAH',
            'variables' => [$variable->id => '5'],
            'evidence_photo' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertRedirect('/mitra/surveys/'.$this->survey->id)->assertSessionHasNoErrors();

        $entry->refresh();
        $this->assertSame(SurveyEntry::STATUS_SUBMITTED, $entry->entry_status);
        $this->assertSame('RT 002 RW 001', $entry->sls);
        $this->assertSame(1, (int) $this->survey->assignments()->where('mitra_id', $mitra->id)->value('current_progress'));
    }

    public function test_invalid_rows_cancel_the_whole_import(): void
    {
        $this->import([
            '31,01,PULAU TIDUNG,mitra@bps.go.id,RT 002 RW 001,Ahmad Fauzi,1',
            '31,01,PULAU ANTAH,mitra@bps.go.id,RT 002 RW 001,Ahmad Fauzi,2',
            '31,01,PULAU TIDUNG,bukan.mitra@example.com,RT 002 RW 001,Orang Lain,3',
            '31,01,PULAU TIDUNG,admin@bps.go.id,RT 002 RW 001,Admin,4',
            '31,01,PULAU TIDUNG,mitra@bps.go.id,RT 002 RW 001,Ahmad Fauzi,100',
            '31,01,PULAU TIDUNG,mitra@bps.go.id,RT 002 RW 001,Ahmad Fauzi,1',
        ])->assertSessionHasErrors('file');

        $message = session('errors')->first('file');
        $this->assertStringContainsString("Baris 3: Kelurahan 'PULAU ANTAH' tidak terdaftar", $message);
        $this->assertStringContainsString("Baris 4: Email 'bukan.mitra@example.com' bukan akun mitra aktif", $message);
        $this->assertStringContainsString("Baris 5: Email 'admin@bps.go.id' bukan akun mitra aktif", $message);
        $this->assertStringContainsString("Baris 6: No Urut Ruta '100' harus angka 1–99", $message);
        $this->assertStringContainsString('Baris 7: ruta ganda dengan baris 2', $message);
        $this->assertSame(0, $this->survey->assignments()->count());
        $this->assertSame(0, $this->survey->entries()->count());
    }

    public function test_import_replaces_the_whole_allocation_and_is_blocked_after_filling(): void
    {
        $this->import([
            '31,01,PULAU TIDUNG,mitra@bps.go.id,RT 002 RW 001,Ahmad Fauzi,1',
            '31,01,PULAU TIDUNG,siti.nurhaliza@gmail.com,RT 002 RW 001,Siti Nurhaliza,2',
        ])->assertSessionHasNoErrors();
        $this->assertSame(2, $this->survey->assignments()->count());

        // Impor berikutnya mengganti seluruh alokasi, bukan menambahkannya.
        $this->import(['31,01,PULAU PARI,mitra@bps.go.id,RT 001 RW 001,Ahmad Fauzi,1'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', fn (string $status): bool => str_contains($status, 'Alokasi lama diganti.'))
            ->assertSessionHas('import_success');
        $this->assertSame(1, $this->survey->assignments()->count());
        $this->assertSame(['RT 001 RW 001'], $this->survey->entries()->pluck('sls')->all());
        $this->assertSame(1, (int) $this->survey->fresh()->total_target);

        // Setelah mitra mulai mengisi, alokasi tidak bisa diganti lewat impor.
        $this->survey->entries()->update(['entry_status' => SurveyEntry::STATUS_DRAFT]);
        $this->import(['31,01,PULAU TIDUNG,mitra@bps.go.id,RT 002 RW 001,Ahmad Fauzi,1'])
            ->assertSessionHasErrors('file');
        $this->assertSame(['RT 001 RW 001'], $this->survey->entries()->pluck('sls')->all());

        $this->actingAs($this->admin)->get('/admin/surveys/'.$this->survey->id.'/assignments')
            ->assertOk()
            ->assertSee('mengganti seluruh alokasi')
            ->assertDontSee('name="import_mode"', false);
    }

    public function test_file_without_email_column_is_rejected_and_pegawai_cannot_import(): void
    {
        $file = UploadedFile::fake()->createWithContent('lama.csv', "Kode Prov,Kode Kab,Kecamatan,Kelurahan,Kode NKS,SLS,PPL,No Urut Ruta\n31,01,X,PULAU TIDUNG,50002,RT 1,Ahmad Fauzi,1\n");

        $this->actingAs($this->admin)->from('/admin/surveys/'.$this->survey->id.'/assignments')
            ->post('/admin/surveys/'.$this->survey->id.'/assignments/import', ['file' => $file])
            ->assertSessionHasErrors('file');

        $pegawai = User::query()->where('email', 'pegawai@bps.go.id')->firstOrFail();
        $this->actingAs($pegawai)->post('/admin/surveys/'.$this->survey->id.'/assignments/import', ['file' => $file])
            ->assertForbidden();
    }

    /**
     * @param  list<string>  $lines
     */
    private function import(array $lines): TestResponse
    {
        $file = UploadedFile::fake()->createWithContent('alokasi.csv', implode("\n", [self::HEADER, ...$lines])."\n");

        return $this->actingAs($this->admin)
            ->from('/admin/surveys/'.$this->survey->id.'/assignments')
            ->post('/admin/surveys/'.$this->survey->id.'/assignments/import', ['file' => $file]);
    }
}
