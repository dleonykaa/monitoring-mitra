<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\FasihImport;
use App\Models\FasihProgressRow;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FasihMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private const HEADER = 'userId,username,email,roleName,totalPetugas,regionCode,totalRegion,statusBreakdown';

    private User $admin;

    private Survey $survey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->admin = User::query()->where('email', 'admin@bps.go.id')->firstOrFail();
        $this->survey = Survey::query()->create([
            'type' => Survey::TYPE_CAPI,
            'title' => 'Survei CAPI Uji',
            'created_by' => $this->admin->id,
            'total_target' => 0,
            'start_date' => '2026-09-01',
            'end_date' => '2026-10-31',
            'status' => 'Berjalan',
        ]);
    }

    public function test_admin_can_open_empty_monitoring_page(): void
    {
        $this->actingAs($this->admin)->get($this->url())
            ->assertOk()
            ->assertSee('Pilih survei')
            ->assertSee('Survei CAPI Uji')
            ->assertSee('Pilih atau seret file CSV ke sini')
            ->assertDontSee('Unduh Excel');
    }

    public function test_non_admin_cannot_access_admin_monitoring(): void
    {
        $mitra = User::query()->role('mitra')->firstOrFail();
        $pegawai = User::query()->where('email', 'pegawai@bps.go.id')->firstOrFail();

        $this->actingAs($mitra)->get($this->url())->assertForbidden();
        $this->actingAs($mitra)->post('/admin/monitoring/progres/import', ['survey_id' => $this->survey->id, 'file' => $this->csvFile([])])->assertForbidden();
        $this->actingAs($pegawai)->post('/admin/monitoring/progres/import', ['survey_id' => $this->survey->id, 'file' => $this->csvFile([])])->assertForbidden();
        $this->assertSame(0, FasihImport::query()->count());
    }

    public function test_pegawai_can_view_monitoring_without_import_controls(): void
    {
        $pegawai = User::query()->where('email', 'pegawai@bps.go.id')->firstOrFail();
        $this->importRows([$this->csvLine('a@example.com', '3101020001000600', 10, 'APPROVED BY Pengawas:4 | OPEN:6')]);

        $this->actingAs($pegawai)->get('/pegawai/monitoring/progres?survey='.$this->survey->id)
            ->assertOk()
            ->assertSee('Survei CAPI Uji')
            ->assertSeeInOrder(['Progres submit', '40,0%', 'dari 10 dokumen'])
            ->assertDontSee('Unduh Excel')
            ->assertDontSee('Import CSV')
            ->assertDontSee('Hapus data ini');

        $this->actingAs($pegawai)->get('/pegawai/monitoring/progres/export?survey='.$this->survey->id)->assertOk()->assertDownload();
        $this->actingAs($pegawai)->get('/admin/monitoring/progres?survey='.$this->survey->id)->assertForbidden();
    }

    public function test_fasih_data_cannot_be_imported_into_papi_survey(): void
    {
        $papiSurvey = Survey::query()->create([
            'title' => 'Survei PAPI Uji',
            'created_by' => $this->admin->id,
            'total_target' => 0,
            'start_date' => '2026-09-01',
            'end_date' => '2026-10-31',
            'status' => 'Draft',
        ]);

        $this->actingAs($this->admin)->post('/admin/monitoring/progres/import', [
            'survey_id' => $papiSurvey->id,
            'file' => $this->csvFile([$this->csvLine('a@example.com', '3101020001000600', 10, 'OPEN:10')]),
        ])->assertStatus(422);

        $this->assertSame(0, FasihImport::query()->count());
    }

    public function test_admin_creates_capi_survey_with_first_fasih_scrape(): void
    {
        $this->actingAs($this->admin)->post('/admin/surveys', [
            'type' => Survey::TYPE_CAPI,
            'title' => 'SE2026 CAPI',
            'start_date' => '2026-09-01',
            'end_date' => '2026-10-31',
            'file' => $this->csvFile([
                $this->csvLine('a@example.com', '3101020001000600', 80, 'APPROVED BY Pengawas:20 | OPEN:60'),
            ]),
        ])->assertSessionHasNoErrors();

        $survey = Survey::query()->where('title', 'SE2026 CAPI')->sole();
        $this->assertTrue($survey->isCapi());
        $this->assertSame('Berjalan', $survey->status);
        $this->assertSame(80, (int) $survey->total_target);
        $this->assertSame(1, $survey->fasihImports()->count());

        $this->actingAs($this->admin)->get('/admin/monitoring/progres?survey='.$survey->id)
            ->assertOk()
            ->assertSeeInOrder(['Progres submit', '25,0%', 'dari 80 dokumen']);
    }

    public function test_capi_survey_requires_scrape_file(): void
    {
        $this->actingAs($this->admin)->from('/admin/surveys/create')->post('/admin/surveys', [
            'type' => Survey::TYPE_CAPI,
            'title' => 'CAPI Tanpa File',
            'start_date' => '2026-09-01',
            'end_date' => '2026-10-31',
        ])->assertSessionHasErrors('file');

        $this->assertFalse(Survey::query()->where('title', 'CAPI Tanpa File')->exists());
    }

    public function test_papi_survey_progress_is_built_from_allocation_and_entries(): void
    {
        $mitra = User::query()->role('mitra')->firstOrFail();
        $district = District::query()->where('code', '3101020')->firstOrFail();
        $papi = Survey::query()->create([
            'title' => 'Survei PAPI Monitoring',
            'created_by' => $this->admin->id,
            'total_target' => 4,
            'start_date' => '2026-09-01',
            'end_date' => '2026-10-31',
            'status' => 'Berjalan',
        ]);
        $assignment = $papi->assignments()->create(['mitra_id' => $mitra->id, 'target' => 4, 'current_progress' => 1]);
        foreach (['submitted', 'draft'] as $status) {
            $papi->entries()->create([
                'survey_assignment_id' => $assignment->id,
                'district_id' => $district->id,
                'sls' => 'SLS Uji',
                'entry_status' => $status,
                'submitted_at' => $status === 'submitted' ? now() : null,
            ]);
        }

        $this->actingAs($this->admin)->get('/admin/monitoring/progres?survey='.$papi->id)
            ->assertOk()
            ->assertSee('Dihitung langsung dari alokasi dan entri mitra')
            ->assertSeeInOrder(['Progres submit', '25,0%', 'dari 4 ruta'])
            // Survei PAPI menampilkan grafik entri terkirim per hari; draft tidak dihitung.
            ->assertSeeInOrder(['Entri per hari', 'Hari ini', '1', 'Tertinggi'])
            ->assertSee(now('Asia/Jakarta')->locale('id')->translatedFormat('l, d M Y').': 1 entri')
            ->assertSee('Minggu ini')
            ->assertSee('minggu=1', false)
            ->assertDontSee('Import CSV');

        // Minggu sebelumnya tidak memuat entri hari ini dan bisa kembali ke minggu ini.
        $this->actingAs($this->admin)->get('/admin/monitoring/progres?survey='.$papi->id.'&minggu=1')
            ->assertOk()
            ->assertSee('1 minggu lalu')
            ->assertDontSee(': 1 entri')
            ->assertDontSee('Hari ini');
        // Minggu di luar periode survei dibatasi ke minggu paling awal.
        $this->actingAs($this->admin)->get('/admin/monitoring/progres?survey='.$papi->id.'&minggu=500')
            ->assertOk()
            ->assertSee(Carbon::parse('2026-09-01')->locale('id')->translatedFormat('l, d M Y'));

        // Kartu Peringatan ada di bawah tabel dan hanya memuat jadwal serta capaian checkpoint,
        // bukan mitra yang lama tidak mengirim entri.
        $papi->entries()->where('entry_status', 'submitted')->update(['submitted_at' => now()->subDays(5)]);
        $this->actingAs($this->admin)->get('/admin/monitoring/progres?survey='.$papi->id.'&mode=mitra')
            ->assertOk()
            ->assertSee('waktu survei sudah berjalan')
            ->assertSeeInOrder(['Progres per Mitra', 'Peringatan'])
            ->assertDontSee('Belum update');

        $this->actingAs($this->admin)->get('/admin/monitoring/progres?survey='.$papi->id.'&mode=mitra')
            ->assertOk()
            ->assertSee($mitra->name);
    }

    public function test_monitoring_lists_only_running_surveys_until_marked_finished(): void
    {
        $draft = Survey::query()->create([
            'title' => 'Survei Masih Draft',
            'created_by' => $this->admin->id,
            'total_target' => 0,
            'start_date' => '2026-09-01',
            'end_date' => '2026-10-31',
            'status' => 'Draft',
        ]);

        $this->actingAs($this->admin)->get('/admin/monitoring/progres')
            ->assertOk()
            ->assertSee('Survei CAPI Uji')
            ->assertDontSee('Survei Masih Draft');

        // Survei Draft yang diminta lewat URL tidak ditampilkan; kembali ke survei berjalan.
        $this->actingAs($this->admin)->get('/admin/monitoring/progres?survey='.$draft->id)
            ->assertOk()
            ->assertViewHas('selectedSurvey', fn (?Survey $survey): bool => $survey?->isNot($draft) ?? true);

        $this->actingAs($this->admin)->get('/admin/surveys/'.$this->survey->id)
            ->assertOk()
            ->assertSee('Tandai selesai');

        $this->actingAs($this->admin)->post('/admin/surveys/'.$this->survey->id.'/status', ['status' => 'Selesai'])
            ->assertRedirect('/admin/surveys/'.$this->survey->id);
        $this->assertSame('Selesai', $this->survey->fresh()->status);

        $this->actingAs($this->admin)->get('/admin/surveys/'.$this->survey->id)
            ->assertOk()
            ->assertDontSee('Tandai selesai')
            ->assertDontSee('Buka monitoring');

        $pegawai = User::query()->where('email', 'pegawai@bps.go.id')->firstOrFail();
        $this->actingAs($pegawai)->get('/pegawai/monitoring/progres')
            ->assertOk()
            ->assertDontSee('Survei CAPI Uji')
            ->assertDontSee('Survei Masih Draft');
        $this->actingAs($pegawai)->post('/admin/surveys/'.$draft->id.'/status', ['status' => 'Selesai'])->assertForbidden();
    }

    public function test_survey_title_must_be_unique_when_creating_and_editing(): void
    {
        $this->actingAs($this->admin)->from('/admin/surveys/create')->post('/admin/surveys', [
            'type' => Survey::TYPE_PAPI,
            'title' => 'Survei CAPI Uji',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ])->assertSessionHasErrors(['title' => 'Nama survei/sensus sudah dipakai. Gunakan nama lain.']);
        $this->assertSame(1, Survey::query()->where('title', 'Survei CAPI Uji')->count());

        $other = Survey::query()->create([
            'title' => 'Survei Lain',
            'created_by' => $this->admin->id,
            'total_target' => 0,
            'start_date' => '2026-09-01',
            'end_date' => '2026-10-31',
            'status' => 'Draft',
        ]);

        $this->actingAs($this->admin)->put('/admin/surveys/'.$other->id, [
            'title' => 'Survei CAPI Uji',
            'start_date' => '2026-09-01',
            'end_date' => '2026-10-31',
        ])->assertSessionHasErrors('title');

        // Menyimpan ulang dengan judul sendiri tetap boleh.
        $this->actingAs($this->admin)->put('/admin/surveys/'.$other->id, [
            'title' => 'Survei Lain',
            'start_date' => '2026-09-01',
            'end_date' => '2026-10-31',
        ])->assertSessionHasNoErrors();
    }

    public function test_reaching_target_does_not_finish_survey_automatically(): void
    {
        $mitra = User::query()->role('mitra')->firstOrFail();
        $papi = Survey::query()->create([
            'title' => 'Survei Target Tercapai',
            'created_by' => $this->admin->id,
            'total_target' => 1,
            'start_date' => '2026-09-01',
            'end_date' => '2026-10-31',
            'status' => 'Berjalan',
        ]);
        $papi->assignments()->create(['mitra_id' => $mitra->id, 'target' => 1, 'current_progress' => 1]);

        $this->actingAs($this->admin)->get('/admin/surveys')->assertOk();

        $this->assertSame('Berjalan', $papi->fresh()->status);
        $this->actingAs($this->admin)->get('/admin/monitoring/progres?survey='.$papi->id)
            ->assertOk()
            ->assertViewHas('selectedSurvey', fn (?Survey $survey): bool => $survey?->is($papi) ?? false);
    }

    public function test_import_updates_capi_survey_target(): void
    {
        $this->importRows([
            $this->csvLine('a@example.com', '3101020001000600', 100, 'OPEN:100'),
            $this->csvLine('b@example.com', '3101020001000800', 50, 'OPEN:50'),
        ]);

        $this->assertSame(150, $this->survey->refresh()->total_target);
        $this->assertSame($this->survey->id, FasihImport::query()->sole()->survey_id);
    }

    public function test_import_parses_region_code_and_groups_statuses(): void
    {
        $mitra = User::query()->role('mitra')->firstOrFail();

        $this->actingAs($this->admin)->post('/admin/monitoring/progres/import', [
            'survey_id' => $this->survey->id,
            'file' => $this->csvFile([
                $this->csvLine('a@example.com', '3101020001000600', 173, 'APPROVED BY Pengawas:127 | DRAFT:22 | SUBMITTED BY Pencacah:21 | REJECTED BY Pengawas:2 | EDITED BY Admin Kabupaten:1'),
                $this->csvLine('a@example.com', '3101020001000800', 95, 'DRAFT:33 | OPEN:28 | SUBMITTED BY Pencacah:24 | REJECTED BY Pengawas:7 | APPROVED BY Pengawas:3'),
                $this->csvLine(strtoupper($mitra->email), '3101010002001301', 125, 'SUBMITTED BY Pencacah:94 | DRAFT:28 | OPEN:3'),
            ]),
        ])->assertRedirect($this->url())->assertSessionHasNoErrors();

        $import = FasihImport::query()->sole();
        $this->assertSame(3, $import->row_count);
        $this->assertSame($this->admin->id, $import->user_id);

        $row = FasihProgressRow::query()->where('region_code', '3101020001000600')->sole();
        $this->assertSame('3101020', $row->district_code);
        $this->assertSame('3101020001', $row->village_code);
        $this->assertSame('31010200010006', $row->sls_code);
        $this->assertSame(173, $row->total_region);
        $this->assertSame(2, $row->open_count);
        $this->assertSame(22, $row->draft_count);
        $this->assertSame(148, $row->submit_count);
        $this->assertSame(1, $row->other_count);
        $this->assertSame(1, $row->status_breakdown['EDITED BY Admin Kabupaten']);
        $this->assertNull($row->user_id);

        $matchedRow = FasihProgressRow::query()->where('region_code', '3101010002001301')->sole();
        $this->assertSame($mitra->id, $matchedRow->user_id);
        $this->assertSame(strtolower($mitra->email), $matchedRow->email);
    }

    public function test_recap_pages_show_totals_per_region_and_mitra(): void
    {
        $mitra = User::query()->role('mitra')->firstOrFail();
        $this->importRows([
            $this->csvLine('a@example.com', '3101020001000600', 100, 'APPROVED BY Pengawas:60 | DRAFT:10 | OPEN:30'),
            $this->csvLine('a@example.com', '3101020002000100', 50, 'OPEN:50'),
            $this->csvLine($mitra->email, '3101010002001301', 40, 'SUBMITTED BY Pencacah:40'),
        ]);

        $this->actingAs($this->admin)->get($this->url())
            ->assertOk()
            ->assertSee('Progres per Kecamatan')
            ->assertSeeInOrder(['Progres submit', '52,6%', '100', 'dari 190 dokumen'])
            ->assertSee('Kepulauan Seribu Utara')
            ->assertSee('Kepulauan Seribu Selatan')
            // Baris kecamatan bisa dibuka bertingkat: desa lalu SLS ikut dirender tersembunyi.
            ->assertSee('data-expandable', false)
            ->assertSeeInOrder(['Kepulauan Seribu Utara', 'Pulau Panggang', 'RT 006 RW 01', 'a@example.com']);

        $this->actingAs($this->admin)->get($this->url().'&kecamatan=3101020')
            ->assertOk()
            ->assertSee('Progres per Desa/Kelurahan')
            ->assertSeeInOrder(['Kab. Kepulauan Seribu', 'Kepulauan Seribu Utara'])
            ->assertSee('Pulau Panggang')
            ->assertSee('Pulau Kelapa')
            ->assertDontSee('Pulau Pari</a>', false);

        $this->actingAs($this->admin)->get($this->url().'&kecamatan=3101020&desa=3101020001')
            ->assertOk()
            ->assertSee('Progres per SLS')
            ->assertSee('RT 006 RW 01')
            ->assertSee('a@example.com')
            ->assertSee('dari 100 dokumen');

        $this->actingAs($this->admin)->get($this->url().'&kecamatan=3101010&desa=3101010002&sls=31010100020013')
            ->assertOk()
            ->assertSee('Progres per Mitra')
            ->assertSee($mitra->name)
            ->assertDontSee('a@example.com');

        $this->actingAs($this->admin)->get($this->url().'&mode=mitra')
            ->assertOk()
            ->assertSee('Progres per Mitra')
            ->assertSee($mitra->name)
            ->assertSeeInOrder(['Nama Pencacah', 'SLS', 'Beban'])
            ->assertSeeInOrder([$mitra->name, $mitra->email])
            // Baris mitra bisa dibuka: SLS yang dipegangnya ikut dirender tersembunyi.
            ->assertSee('data-expandable', false)
            ->assertSeeInOrder([$mitra->email, 'RT 003 RW 004'])
            ->assertSee('a@example.com')
            ->assertDontSee('Belum terhubung ke akun SIMPROCA');

        $this->actingAs($this->admin)->get($this->url().'&mode=mitra&mitra='.urlencode($mitra->email))
            ->assertOk()
            ->assertSee('Progres per SLS')
            ->assertSeeInOrder(['Semua mitra', $mitra->name])
            ->assertSee('RT 003 RW 004')
            ->assertDontSee('RT 006 RW 01');
    }

    public function test_nama_petugas_column_is_used_as_pencacah_name(): void
    {
        $mitra = User::query()->role('mitra')->firstOrFail();
        $line = fn (string $email, string $regionCode, string $breakdown, string $name): string => sprintf(
            '"uid","%s","%s","Pencacah","10","=""%s""","10","%s","%s"', $email, $email, $regionCode, $breakdown, $name
        );

        $file = UploadedFile::fake()->createWithContent('fasih_progress_dengan_namaPetugas.csv', implode("\n", [
            self::HEADER.',namaPetugas',
            $line('sjuhroh12@gmail.com', '3101020001000600', 'OPEN:4 | APPROVED BY Pengawas:6', 'Siti Juhroh'),
            $line($mitra->email, '3101020001000800', 'OPEN:10', 'Nama Dari FASIH'),
            $line('tanpa.nama@example.com', '3101010001000100', 'DRAFT:10', ''),
        ])."\n");

        $this->actingAs($this->admin)->post('/admin/monitoring/progres/import', ['survey_id' => $this->survey->id, 'file' => $file])
            ->assertSessionHasNoErrors();

        $this->assertSame('Siti Juhroh', FasihProgressRow::query()->where('email', 'sjuhroh12@gmail.com')->value('pencacah_name'));
        $this->assertNull(FasihProgressRow::query()->where('email', 'tanpa.nama@example.com')->value('pencacah_name'));

        $this->actingAs($this->admin)->get($this->url().'&mode=mitra')
            ->assertOk()
            ->assertSeeInOrder(['Siti Juhroh', 'sjuhroh12@gmail.com'])
            ->assertSeeInOrder(['Nama Dari FASIH', strtolower($mitra->email)])
            ->assertDontSee($mitra->name)
            ->assertSee('tanpa.nama@example.com');
    }

    public function test_admin_can_download_current_recap_as_excel(): void
    {
        $this->importRows([
            $this->csvLine('a@example.com', '3101020001000600', 100, 'APPROVED BY Pengawas:60 | OPEN:40'),
        ]);

        $response = $this->actingAs($this->admin)->get($this->url(['kecamatan' => '3101020'], '/admin/monitoring/progres/export'));

        $response->assertOk()->assertDownload();
        $this->assertStringContainsString('progres-survei-capi-uji-per-desa', $response->headers->get('content-disposition'));
    }

    public function test_export_without_any_import_returns_not_found(): void
    {
        $this->actingAs($this->admin)->get($this->url([], '/admin/monitoring/progres/export'))->assertNotFound();
    }

    public function test_unknown_region_code_is_left_out_of_monitoring(): void
    {
        $this->importRows([
            $this->csvLine('x@example.com', '3101000000000000', 3, 'SUBMITTED RESPONDENT:2 | DRAFT:1'),
            $this->csvLine('a@example.com', '3101020001000600', 10, 'APPROVED BY Pengawas:4 | OPEN:6'),
        ]);

        $this->actingAs($this->admin)->get($this->url())
            ->assertOk()
            ->assertDontSee('Tidak terpetakan')
            ->assertDontSee('<code>000</code>', false)
            ->assertSeeInOrder(['Progres submit', '40,0%', 'dari 10 dokumen']);

        $this->actingAs($this->admin)->get($this->url().'&mode=mitra')
            ->assertOk()
            ->assertDontSee('x@example.com');

        // Data mentah tetap tersimpan utuh.
        $row = FasihProgressRow::query()->where('email', 'x@example.com')->sole();
        $this->assertSame(2, $row->other_count);
        $this->assertSame(1, $row->draft_count);
    }

    public function test_latest_import_is_shown_and_older_import_remains_available(): void
    {
        $this->importRows([$this->csvLine('a@example.com', '3101020001000600', 100, 'OPEN:100')], 'lama.csv');
        $this->importRows([$this->csvLine('a@example.com', '3101020001000600', 100, 'APPROVED BY Pengawas:100')], 'baru.csv');

        $oldImport = FasihImport::query()->where('file_name', 'lama.csv')->sole();

        $this->actingAs($this->admin)->get($this->url())
            ->assertOk()
            ->assertSee('baru.csv')
            ->assertDontSee('Data lama')
            ->assertSee('100,0%');

        $this->actingAs($this->admin)->get($this->url(['import' => $oldImport->id]))
            ->assertOk()
            ->assertSee('Data lama')
            ->assertSee('lama.csv')
            ->assertSeeInOrder(['Progres submit', '0,0%', 'dari 100 dokumen']);
    }

    public function test_admin_can_delete_an_import(): void
    {
        $this->importRows([$this->csvLine('a@example.com', '3101020001000600', 10, 'OPEN:10')]);
        $import = FasihImport::query()->sole();

        $this->actingAs($this->admin)->delete('/admin/monitoring/progres/imports/'.$import->id)
            ->assertRedirect($this->url());

        $this->assertSame(0, FasihImport::query()->count());
        $this->assertSame(0, FasihProgressRow::query()->count());
    }

    public function test_file_with_missing_columns_is_rejected(): void
    {
        $file = UploadedFile::fake()->createWithContent('salah.csv', "nama,wilayah\nBudi,3101020001000600\n");

        $this->actingAs($this->admin)->from($this->url())
            ->post('/admin/monitoring/progres/import', ['survey_id' => $this->survey->id, 'file' => $file])
            ->assertRedirect($this->url())
            ->assertSessionHasErrors('file');

        $this->assertSame(0, FasihImport::query()->count());
    }

    public function test_invalid_rows_reject_the_whole_file(): void
    {
        $this->actingAs($this->admin)->from($this->url())
            ->post('/admin/monitoring/progres/import', ['survey_id' => $this->survey->id, 'file' => $this->csvFile([
                $this->csvLine('a@example.com', '3101020001000600', 10, 'OPEN:10'),
                $this->csvLine('a@example.com', '31010200', 10, 'OPEN:10'),
                $this->csvLine('a@example.com', '3101020001000800', 10, 'status tanpa angka'),
            ])])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, FasihImport::query()->count());
        $this->assertSame(0, FasihProgressRow::query()->count());
    }

    public function test_non_csv_file_is_rejected(): void
    {
        $this->actingAs($this->admin)->from($this->url())
            ->post('/admin/monitoring/progres/import', ['survey_id' => $this->survey->id, 'file' => UploadedFile::fake()->create('foto.png', 10, 'image/png')])
            ->assertSessionHasErrors('file');
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function url(array $params = [], string $path = '/admin/monitoring/progres'): string
    {
        return $path.'?'.http_build_query(['survey' => $this->survey->id, ...$params]);
    }

    /**
     * @param  list<string>  $lines
     */
    private function importRows(array $lines, string $fileName = 'fasih_progress.csv'): void
    {
        $this->actingAs($this->admin)->post('/admin/monitoring/progres/import', ['survey_id' => $this->survey->id, 'file' => $this->csvFile($lines, $fileName)])
            ->assertSessionHasNoErrors();
    }

    /**
     * @param  list<string>  $lines
     */
    private function csvFile(array $lines, string $fileName = 'fasih_progress.csv'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($fileName, implode("\n", [self::HEADER, ...$lines])."\n");
    }

    /**
     * Menulis satu baris dengan format yang sama seperti ekspor FASIH (kode wilayah sebagai formula).
     */
    private function csvLine(string $email, string $regionCode, int $total, string $breakdown): string
    {
        return sprintf('"uid-%s","%s","%s","Pencacah","100","=""%s""","%d","%s"', md5($email), $email, $email, $regionCode, $total, $breakdown);
    }
}
