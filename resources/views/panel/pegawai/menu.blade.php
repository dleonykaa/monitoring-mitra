<a class="{{ request()->is('pegawai/dashboard') ? 'active' : '' }}" href="/pegawai/dashboard">Dashboard Tim</a>
<a class="{{ request()->is('pegawai/surveys*') ? 'active' : '' }}" href="/pegawai/surveys">Survei</a>
<a class="{{ request()->is('pegawai/entries*') ? 'active' : '' }}" href="/pegawai/entries">Data Entri</a>
<a class="{{ request()->is('pegawai/updates*') ? 'active' : '' }}" href="/pegawai/updates">Riwayat Update</a>
<a class="{{ request()->is('pegawai/mitra*') ? 'active' : '' }}" href="/pegawai/mitra">Daftar Mitra</a>
