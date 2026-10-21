export function buildItinerary({day, days, nights, route, hotel, mode, game = "Dots Board Game Cafe"}) {
    const arrivingEarly = nights < days;
    const arrival = mode === 'whoosh' ? 'Padalarang → feeder / kendaraan lanjutan ke Bandung' : mode === 'shuttle' ? 'Pool shuttle Bandung sesuai tiket' : hotel.name;
    const arrivalNote = mode === 'whoosh' ? 'Periksa tujuan kereta, koneksi feeder, dan akses ke hotel. Tambahkan waktu untuk pindah moda.' : mode === 'shuttle' ? 'Pilih pool yang paling dekat dengan hotel. Perjalanan pool–hotel memakai kendaraan lokal.' : 'Langsung menuju hotel. Konfirmasi parkir untuk jumlah mobil rombongan.';
    const lastDay = day === days;
    const checkout = ['07.00', lastDay ? 'Sarapan, checkout & urus barang' : 'Sarapan dekat hotel', lastDay ? `Checkout dari ${hotel.name}. Titip barang hanya jika rute pulang melewati hotel; untuk Ciwidey, lebih praktis dibawa di kendaraan.` : `Mulai dari ${hotel.name}. Sarapan termasuk paket hanya jika dikonfirmasi saat booking.`, hotel.name];
    const finish = lastDay ? ['17.00', 'Ambil barang → pulang ke Jakarta', 'Jika barang dititipkan, kembali ke hotel lebih dulu. Sisakan 60–90 menit sebelum keberangkatan stasiun/pool dan ikuti aturan operator. Jam pulang sesuaikan tiket.', mode === 'car' || mode === 'rental' ? 'Jakarta' : 'Stasiun Bandung'] : ['19.30', `Kembali ke ${hotel.name}`, 'Istirahat, isi daya ponsel, dan siapkan barang untuk besok.', hotel.name];
    if (day === 0) return [
        ['Sore', 'Berangkat dari Jakarta', 'Untuk durasi dengan jumlah malam sama dengan jumlah hari wisata, berangkat sehari sebelum hari pertama. Sesuaikan jam dengan tiket dan lalu lintas.', 'Jakarta'],
        ['Malam', `Tiba → check-in di ${hotel.name}`, 'Konfirmasi jam tiba ke hotel. Siapkan identitas dan bukti reservasi; jangan mengandalkan kamar tersedia tanpa booking.', hotel.name],
        ['Setelah check-in', 'Makan dekat hotel & istirahat', 'Pilih tempat yang masih buka di sekitar hotel. Tambahan satu makan malam sudah dialokasikan pada durasi ini.', hotel.name]
    ];
    if (route === 'custom') return [
        day === 1 && arrivingEarly ? ['Pagi', 'Jakarta → Bandung → penginapan sendiri', 'Sesuaikan jam tiba dan konfirmasi penitipan barang sebelum check-in.', 'Bandung'] : checkout,
        ['Siang', 'Makan siang pilihan kalian', 'Pilih tempat searah penginapan. Makan, kopi, dan jajanan berbagi budget makan harian.', 'Bandung'],
        ['Sore', day === 1 ? 'Check-in → kumpul santai / board game pilihan sendiri' : 'Ngopi atau jalan santai', 'Ikuti jam check-in pada booking. Board game dialokasikan satu sesi per perjalanan; ubah menjadi Rp0 jika tidak bermain.', 'Bandung'],
        lastDay ? finish : ['Malam', 'Makan malam → kembali ke penginapan', 'Pilih tempat dekat penginapan.', 'Bandung']
    ];
    if (day === 1) return [
        arrivingEarly ? ['06.00', 'Berangkat dari Jakarta', 'Target tiba sekitar 10.00. Ini asumsi perencanaan, bukan jadwal kereta/shuttle. Kemacetan dapat menggeser seluruh agenda.', 'Jakarta'] : ['08.00', `Sarapan di sekitar ${hotel.name}`, 'Sudah check-in malam sebelumnya, jadi bisa mulai lebih santai.', hotel.name],
        arrivingEarly ? ['10.00', `Tiba: ${arrival}`, arrivalNote, mode === 'car' || mode === 'rental' ? hotel.name : 'Bandung'] : ['09.00', 'Berangkat ke pusat kota', 'Bawa tas kecil saja; barang utama tetap di kamar.', 'Jalan Braga'],
        ['10.30', arrivingEarly ? `Titip barang di ${hotel.name}` : 'Braga & Asia Afrika', arrivingEarly ? 'Hubungi hotel untuk memastikan penitipan bagasi. Check-in umumnya sore; early check-in tergantung ketersediaan dan bisa berbayar.' : 'Jalan kaki dan foto area kota lama. Area publik tanpa tiket.', arrivingEarly ? hotel.name : 'Jalan Braga'],
        ['12.00', 'Makan siang: Warung Nasi Ibu Imas', 'Ke Balonggede untuk nasi dan lauk Sunda. Alternatif: makan di sekitar Braga agar tidak berpindah terlalu jauh. Siapkan sekitar Rp35–65 ribu/orang.', 'Warung Nasi Ibu Imas Balonggede'],
        ['14.00', arrivingEarly ? `Check-in & istirahat di ${hotel.name}` : 'Jeda kopi: Warung Kopi Purnama', arrivingEarly ? 'Gunakan jam check-in yang tertera pada booking; 14.00 hanya asumsi. Bila kamar belum siap, lanjut kegiatan ringan di sekitar hotel.' : 'Kopi dan roti srikaya di Alkateri. Alokasikan Rp30–60 ribu dari budget makan/jajan, bukan tambahan gratis.', arrivingEarly ? hotel.name : 'Warung Kopi Purnama Alkateri'],
        ['15.30–18.00', `Main bareng di ${game}`, 'Konfirmasi meja terlebih dahulu. Biaya satu sesi terpisah dari makan/minum; durasi mengikuti paket cafe. Alternatif: jalan sore di Braga.', game],
        ['18.00', 'Makan malam: Sudirman Street', 'Pilih satu tenant sesuai anggaran. Tersedia menu halal dan nonhalal; cek bahan langsung ke penjual. Alternatif: kembali makan dekat hotel.', 'Sudirman Street Day and Night Market'],
        ['20.00', `Kembali ke ${hotel.name}`, 'Istirahat. Jangan memaksakan semua alternatif dikunjungi.', hotel.name]
    ];
    if (route === 'south' && day === 2) return [checkout,
        ['07.30', 'Berangkat ke Ciwidey', 'Siapkan kendaraan PP terlebih dahulu. Alokasikan sekitar 2–3 jam per arah, bisa lebih saat akhir pekan. Jangan gabungkan dengan Lembang.', 'Kawah Putih Ciwidey'],
        ['10.30', 'Kawah Putih', 'Cek status buka, tiket, parkir, angkutan kawasan, dan cuaca. Alokasikan Rp100–150 ribu/orang untuk kunjungan; fasilitas tambahan dapat terpisah.', 'Kawah Putih Ciwidey'],
        ['12.30', 'Makan siang di sekitar Ciwidey', 'Pilih rumah makan di jalur kembali. Lihat daftar harga sebelum memesan; tidak perlu memutar untuk mengejar tempat viral.', 'Ciwidey'],
        ['14.00', lastDay ? 'Mulai perjalanan pulang' : 'Kembali ke Bandung', lastDay ? 'Jika naik mobil, bisa langsung lanjut Jakarta. Untuk kereta/shuttle, pilih tiket malam dengan jeda perjalanan yang cukup; jangan mengandalkan tiba tepat waktu.' : 'Sisakan sore untuk perjalanan dan istirahat. Tidak menambah destinasi jauh.', lastDay && ['car','rental'].includes(mode) ? 'Jakarta' : hotel.name],
        lastDay ? ['Malam', 'Lanjut ke Jakarta sesuai tiket', 'Bila masih harus mengambil barang di hotel, tambahkan waktunya. Ciwidey lebih nyaman untuk 3 hari; untuk 2 hari pertimbangkan rute kota.', ['car','rental'].includes(mode) ? 'Jakarta' : 'Stasiun Bandung'] : ['18.30', 'Makan malam dekat hotel', `Kembali beristirahat di ${hotel.name} setelah makan.`, hotel.name]
    ];
    if (route === 'nature' && day === 2) return [checkout,
        ['08.30', 'Tahura Ir. H. Djuanda', 'Jalan hutan ringan, bawa air dan sepatu nyaman. Alternatif: Tebing Keraton, pilih salah satu dan konfirmasi akses terakhir.', 'Tahura Ir H Djuanda'],
        ['12.00', 'Makan siang di Dago', 'Pilih warung atau kafe yang searah turun ke kota. Cocokkan harga menu dengan anggaran makan harian.', 'Dago Bandung'],
        ['14.00', lastDay ? 'Oleh-oleh opsional: Kartika Sari Dago' : 'Gedung Sate & Gasibu', lastDay ? 'Singgah hanya jika waktu cukup. Belanja oleh-oleh menggunakan budget terpisah, lalu ambil barang di hotel.' : 'Foto area luar atau istirahat jika lelah. Tidak perlu menambah perjalanan ke Lembang hari ini.', lastDay ? 'Kartika Sari Dago' : 'Gedung Sate'],
        lastDay ? finish : ['18.00', 'Makan malam: Cuanki Serayu', `Pilihan makan ringan di Cihapit, lalu kembali ke ${hotel.name}. Jika masih lapar, pilih makan utama sesuai sisa budget.`, 'Cuanki Serayu']
    ];
    if (route === 'nature' && day === 3) return [checkout,
        ['08.00', 'Berangkat ke Lembang', 'Pilih satu: Floating Market untuk kuliner atau Orchid Forest Cikole untuk hutan pinus. Untuk tiket pulang sore, Floating Market biasanya lebih sederhana dari sisi rute.', 'Floating Market Lembang'],
        ['09.30', 'Floating Market atau Orchid Forest', 'Kunjungi satu pilihan saja. Tiket dan aktivitas tambahan berbeda; cek harga resmi sebelum datang.', 'Floating Market Lembang'],
        ['12.00', 'Makan siang di Lembang', 'Makan di kawasan atau rumah makan pada jalur turun. Masukkan ke anggaran makan harian.', 'Lembang'],
        ['13.30', 'Turun ke Bandung & ambil barang', 'Berangkat lebih awal saat akhir pekan. Bila memilih Orchid Forest, hitung waktu tambahan dari Cikole.', hotel.name],finish];
    if (day === 3) return [checkout,
        ['09.00', 'Warung Kopi Purnama atau Roti Gempol', 'Pilih satu untuk sarapan santai. Jika sudah sarapan, cukup minuman atau lewati langkah ini agar anggaran tetap cukup.', 'Warung Kopi Purnama Alkateri'],
        ['10.30', 'Braga & Asia Afrika', 'Jalan kaki di area kota lama. Alternatif bila sudah puas: langsung ke toko oleh-oleh yang searah hotel.', 'Jalan Braga'],
        ['12.00', 'Makan siang: Warung Nasi Ibu Imas', 'Pilih lauk sesuai anggaran. Alternatif: tempat makan dekat hotel agar pengambilan barang lebih mudah.', 'Warung Nasi Ibu Imas Balonggede'],
        ['14.00', 'Ambil barang & menuju titik pulang', 'Jika sudah membawa barang sejak checkout, lanjut menuju stasiun/pool atau perjalanan ke Jakarta. Waktu tersisa menjadi jeda, tidak perlu memaksakan tempat lain.', hotel.name], finish];
    return [checkout,
        ['09.00', 'Sarapan santai & kopi di Roti Gempol', 'Pilih sesuai alokasi makan. Jika sudah sarapan di hotel, cukup minuman atau lewati langkah ini. Cek tempat duduk rombongan.', 'Roti Gempol'],
        ['11.30', 'Makan siang: Cuanki Serayu', 'Pilihan dekat Cihapit. Alternatif sarapan lebih awal: Roti Gempol. Pilih sesuai selera dan jam buka, tidak harus keduanya.', 'Cuanki Serayu'],
        ['13.00', lastDay ? 'Oleh-oleh opsional & kembali ke hotel' : 'Jeda santai di pusat kota', lastDay ? 'Ambil barang sebelum menuju titik pulang. Oleh-oleh belum termasuk total budget.' : 'Istirahat atau pilih satu kafe. Alternatif pertunjukan: Saung Angklung Udjo, hanya jika jadwal dan tiket sudah dikonfirmasi; siapkan Rp85–120 ribu/orang dan kendaraan ke Padasuka.', lastDay ? hotel.name : 'Jalan Braga'],
        lastDay ? finish : ['18.00', 'Makan malam dekat hotel', `Pilih tempat yang searah, lalu istirahat di ${hotel.name}.`, hotel.name]
    ];
}
