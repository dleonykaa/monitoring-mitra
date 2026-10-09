<?php

namespace Database\Seeders;

use App\Models\FasihProgressRow;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Akun uji coba: mitra dari data FASIH (email + namaPetugas) dan pegawai rekaan.
 * Semua akun memakai kata sandi sementara "password123".
 */
class DummyAccountSeeder extends Seeder
{
    public const PASSWORD = 'password123';

    /**
     * Pencacah pada fasih_progress_dengan_namaPetugas.csv. Nama ditulis persis seperti kolom namaPetugas;
     * wahyu.rahmaditama tidak punya namaPetugas sehingga namanya diturunkan dari email. Email dan nomor telepon
     * mitra yang ada di SE2026_66_3101_exportmitra (2 Okt 2026) mengikuti ekspor tersebut.
     *
     * @var list<array{name: string, email: string, phone: string}>
     */
    public const MITRA = [
        ['name' => 'Siti Juhroh', 'email' => 'sjuhroh12@gmail.com', 'phone' => '085715676590'],
        ['name' => 'Siti Rosilah', 'email' => 'raynzico1010@gmail.com', 'phone' => '085881750417'],
        ['name' => 'Siti komalasari', 'email' => 'komalasarisiti867@gmail.com', 'phone' => '081586982498'],
        ['name' => 'Mamik kusumaningsih', 'email' => 'kusumaningsimamik@gmail.com', 'phone' => '08568348824'],
        ['name' => 'Mimin komala', 'email' => 'miminkomala9@gmail.com', 'phone' => '085719103095'],
        ['name' => 'Nurul Fatihah Al Firdausi', 'email' => 'alfirdausi89@gmail.com', 'phone' => '081282903220'],
        ['name' => 'MAHRANI', 'email' => 'mahranienanie9@gmail.com', 'phone' => '081289456204'],
        ['name' => 'Karlinah', 'email' => 'karlinahzakar@gmail.com', 'phone' => '089653764763'],
        ['name' => 'SRI RAHAYU PUJI UTAMI', 'email' => 'srirahayupujiutami303@gmail.com', 'phone' => '083871118106'],
        ['name' => 'Ria Anggraini', 'email' => 'riamubarok2010@gmail.com', 'phone' => '085717644691'],
        ['name' => 'Astri Meilisa Puteri', 'email' => 'pulo0405@gmail.com', 'phone' => '081316925840'],
        ['name' => 'Hilma', 'email' => 'hilmaacenk@gmail.com', 'phone' => '085778912215'],
        ['name' => 'Muhammad Shalahuddin', 'email' => 'uddy2828@gmail.com', 'phone' => '085711065828'],
        ['name' => 'sarmila', 'email' => 'milasarmila62808@gmail.com', 'phone' => '088214359543'],
        ['name' => 'Asniati', 'email' => 'asniati377@gmail.com', 'phone' => '081770760849'],
        ['name' => 'Tantri diana sari', 'email' => 'tantridianasari004@gmail.com', 'phone' => '083813257278'],
        ['name' => 'Nur Humairoh', 'email' => 'umayy6139@gmail.com', 'phone' => '083199530372'],
        ['name' => 'Laiylatul Kodria', 'email' => 'laiylatulkodria13@gmail.com', 'phone' => '085591765106'],
        ['name' => 'Sakina mawadah', 'email' => 'mawadah.skn18@gmail.com', 'phone' => '08558047845'],
        ['name' => 'Muhammad Zainul Mafakhir', 'email' => 'mafakhirmz05@gmail.com', 'phone' => '082211981394'],
        ['name' => 'Windi Popy Guntari', 'email' => 'windipopyg@gmail.com', 'phone' => '085718187114'],
        ['name' => 'Hasan wirayuda', 'email' => 'yudatongkii@gmail.com', 'phone' => '085710773556'],
        ['name' => 'ANJILIRROHMAH', 'email' => 'alr53057@gmail.com', 'phone' => '085183143367'],
        ['name' => 'Rifka nabila', 'email' => 'rifkanabillah@gmail.com', 'phone' => '083874408525'],
        ['name' => 'Uswatun khasanah', 'email' => 'uswatunzema13@gmail.com', 'phone' => '085694384008'],
        ['name' => 'Wahyu Rahmaditama', 'email' => 'wahyu.rahmaditama@gmail.com', 'phone' => '081263281942'],
        // Mitra SE2026 dari ekspor mitra BPS Kepulauan Seribu yang belum ada di data FASIH.
        ['name' => 'Mubarok', 'email' => 'almermubarak2018@gmail.com', 'phone' => '085714870506'],
        ['name' => 'Nur Laeli', 'email' => 'laeli5290@gmail.com', 'phone' => '085691901973'],
        // Mitra pada daftar alokasi PAPI yang belum punya akun: email dan nomor telepon rekaan.
        ['name' => 'Ikhtiyati', 'email' => 'ikhtiyati@gmail.com', 'phone' => '081200001001'],
        ['name' => 'Sri Subandiyah', 'email' => 'sri.subandiyah@gmail.com', 'phone' => '081200001002'],
        ['name' => 'Rosalinda', 'email' => 'rosalinda@gmail.com', 'phone' => '081200001003'],
        ['name' => 'Ayu Rizkiyanah Putri', 'email' => 'arizkiyanahputri@gmail.com', 'phone' => '085893689720'],
        ['name' => 'Rosmalinda', 'email' => 'rosmalinda@gmail.com', 'phone' => '081200001005'],
        ['name' => 'Moch Soib', 'email' => 'moch.soib@gmail.com', 'phone' => '081200001006'],
        ['name' => 'Firqotun Najiyah', 'email' => 'firqotun.najiyah@gmail.com', 'phone' => '081200001007'],
        ['name' => 'DEASY SAFITRI', 'email' => 'deasysafitri915@gmail.com', 'phone' => '085887656569'],
        ['name' => 'Enita Febriyanti', 'email' => 'enita.febriyanti@gmail.com', 'phone' => '081200001009'],
        ['name' => 'Basiroh', 'email' => 'basirohabas792@gmail.com', 'phone' => '087888810872'],
        ['name' => 'Puspita Amelia Ramadhona', 'email' => 'puspita.amelia.ramadhona@gmail.com', 'phone' => '081200001011'],
        ['name' => 'Ropidatul Hijriyah', 'email' => 'ropidatul.hijriyah@gmail.com', 'phone' => '081200001012'],
        ['name' => 'Liana Kurniati', 'email' => 'liana.kurniati@gmail.com', 'phone' => '081200001013'],
    ];

    /**
     * Pegawai rekaan sampai daftar pegawai asli tersedia.
     *
     * @var list<array{name: string, email: string, phone: string}>
     */
    public const PEGAWAI = [
        ['name' => 'Rahmat Hidayat', 'email' => 'rahmat.hidayat@bps.go.id', 'phone' => '085227500087'],
        ['name' => 'Nadia Puspitasari', 'email' => 'nadia.puspitasari@bps.go.id', 'phone' => '089524450601'],
        ['name' => 'Fikri Ardiansyah', 'email' => 'fikri.ardiansyah@bps.go.id', 'phone' => '085706760589'],
        ['name' => 'Dwi Kartika Sari', 'email' => 'dwi.kartika@bps.go.id', 'phone' => '082255907904'],
        ['name' => 'Yoga Pratama', 'email' => 'yoga.pratama@bps.go.id', 'phone' => '087857299570'],
        ['name' => 'Annisa Rahmawati', 'email' => 'annisa.rahmawati@bps.go.id', 'phone' => '089562319742'],
        ['name' => 'Galih Prakoso', 'email' => 'galih.prakoso@bps.go.id', 'phone' => '087856109051'],
        ['name' => 'Siska Amelia', 'email' => 'siska.amelia@bps.go.id', 'phone' => '087832769766'],
        ['name' => 'Marwan', 'email' => 'marwan@bps.go.id', 'phone' => '081358204716'],
    ];

    public function run(): void
    {
        foreach (self::MITRA as $data) {
            $mitra = $this->account($data);
            $mitra->syncRoles(['mitra']);

            // Hubungkan baris FASIH yang sudah diimpor ke akun dengan email yang sama.
            FasihProgressRow::query()->where('email', $data['email'])->update(['user_id' => $mitra->id]);
        }

        foreach (self::PEGAWAI as $data) {
            $this->account($data)->syncRoles(['pegawai_bps']);
        }
    }

    /**
     * @param  array{name: string, email: string, phone: string}  $data
     */
    private function account(array $data): User
    {
        $user = User::withTrashed()->updateOrCreate(
            ['email' => $data['email']],
            ['name' => $data['name'], 'phone' => $data['phone'], 'password' => self::PASSWORD, 'is_active' => true],
        );

        if ($user->trashed()) {
            $user->restore();
        }

        return $user;
    }
}
