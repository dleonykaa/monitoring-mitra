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
     * wahyu.rahmaditama tidak punya namaPetugas sehingga namanya diturunkan dari email.
     *
     * @var list<array{name: string, email: string, phone: string}>
     */
    public const MITRA = [
        ['name' => 'Siti Juhroh', 'email' => 'sjuhroh12@gmail.com', 'phone' => '082207405492'],
        ['name' => 'Siti Rosilah', 'email' => 'raynzico1010@gmail.com', 'phone' => '085723622647'],
        ['name' => 'Siti komalasari', 'email' => 'komalasarisiti867@gmail.com', 'phone' => '081230261549'],
        ['name' => 'Mamik kusumaningsih', 'email' => 'kusumaningsimamik@gmail.com', 'phone' => '087865799424'],
        ['name' => 'Mimin komala', 'email' => 'miminkomala9@gmail.com', 'phone' => '089518234709'],
        ['name' => 'Nurul Fatihah Al Firdausi', 'email' => 'alfirdausi89@gmail.com', 'phone' => '087722614325'],
        ['name' => 'MAHRANI', 'email' => 'mahranienanie9@gmail.com', 'phone' => '085713736367'],
        ['name' => 'Karlinah', 'email' => 'karlinahzakar@gmail.com', 'phone' => '089641321710'],
        ['name' => 'SRI RAHAYU PUJI UTAMI', 'email' => 'srirahayupujiutami303@gmail.com', 'phone' => '082295056533'],
        ['name' => 'Ria Anggraini', 'email' => 'riamubarok2010@gmail.com', 'phone' => '081247308259'],
        ['name' => 'Astri Meilisa Puteri', 'email' => 'pulo0405@gmail.com', 'phone' => '089610306676'],
        ['name' => 'Hilma', 'email' => 'hilmaacenk@gmail.com', 'phone' => '082216057765'],
        ['name' => 'Muhammad Shalahuddin', 'email' => 'uddy2828@gmail.com', 'phone' => '089671739795'],
        ['name' => 'sarmila', 'email' => 'milasarmila62808@gmail.com', 'phone' => '089569457571'],
        ['name' => 'Asniati', 'email' => 'asniati377@gmail.com', 'phone' => '089616970815'],
        ['name' => 'Tantri diana sari', 'email' => 'tantridianasari004@gmail.com', 'phone' => '087821850128'],
        ['name' => 'Nur Humairoh', 'email' => 'umayy6139@gmail.com', 'phone' => '085830405773'],
        ['name' => 'Laiylatul Kodria', 'email' => 'laiylatulkodria13@gmail.com', 'phone' => '081210414018'],
        ['name' => 'Sakina mawadah', 'email' => 'mawadah.skn18@gmail.com', 'phone' => '085725113233'],
        ['name' => 'Muhammad Zainul Mafakhir', 'email' => 'mafakhirmz05@gmail.com', 'phone' => '085208398078'],
        ['name' => 'Windi Popy Guntari', 'email' => 'windipopyg@gmail.com', 'phone' => '087780490251'],
        ['name' => 'Hasan wirayuda', 'email' => 'yudatongkii@gmail.com', 'phone' => '085342730317'],
        ['name' => 'ANJILIRROHMAH', 'email' => 'alr53057@gmail.com', 'phone' => '085324862951'],
        ['name' => 'Rifka nabila', 'email' => 'rifkanabillah@gmail.com', 'phone' => '085294974140'],
        ['name' => 'Uswatun khasanah', 'email' => 'uswatunzema13@gmail.com', 'phone' => '082131848323'],
        ['name' => 'Wahyu Rahmaditama', 'email' => 'wahyu.rahmaditama@gmail.com', 'phone' => '081263281942'],
        // Mitra pada daftar alokasi PAPI yang belum punya akun: email dan nomor telepon rekaan.
        ['name' => 'Ikhtiyati', 'email' => 'ikhtiyati@gmail.com', 'phone' => '081200001001'],
        ['name' => 'Sri Subandiyah', 'email' => 'sri.subandiyah@gmail.com', 'phone' => '081200001002'],
        ['name' => 'Rosalinda', 'email' => 'rosalinda@gmail.com', 'phone' => '081200001003'],
        ['name' => 'Ayu Rizkiyanah Putri', 'email' => 'ayu.rizkiyanah.putri@gmail.com', 'phone' => '081200001004'],
        ['name' => 'Rosmalinda', 'email' => 'rosmalinda@gmail.com', 'phone' => '081200001005'],
        ['name' => 'Moch Soib', 'email' => 'moch.soib@gmail.com', 'phone' => '081200001006'],
        ['name' => 'Firqotun Najiyah', 'email' => 'firqotun.najiyah@gmail.com', 'phone' => '081200001007'],
        ['name' => 'Deasy Safitri', 'email' => 'deasy.safitri@gmail.com', 'phone' => '081200001008'],
        ['name' => 'Enita Febriyanti', 'email' => 'enita.febriyanti@gmail.com', 'phone' => '081200001009'],
        ['name' => 'Basiroh', 'email' => 'basiroh@gmail.com', 'phone' => '081200001010'],
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
