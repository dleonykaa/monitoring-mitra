<a class="{{ request()->is('mitra/dashboard') ? 'active' : '' }}" href="/mitra/dashboard">Dashboard</a>
<a class="{{ request()->is('mitra/surveys*') ? 'active' : '' }}" href="/mitra/surveys">Survei</a>
<a class="{{ request()->is('mitra/entries*') ? 'active' : '' }}" href="/mitra/entries">Data Entri</a>
<a class="{{ request()->is('mitra/updates*') ? 'active' : '' }}" href="/mitra/updates">Riwayat Update</a>
