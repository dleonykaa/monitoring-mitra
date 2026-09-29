<a class="{{ request()->is('admin/dashboard') ? 'active' : '' }}" href="/admin/dashboard">Dashboard</a>
<a class="{{ request()->is('admin/users*') ? 'active' : '' }}" href="/admin/users">Manajemen Pengguna</a>
<a class="{{ request()->is('admin/roles*') ? 'active' : '' }}" href="/admin/roles">Role & Hak Akses</a>
<a class="{{ request()->is('admin/teams*') ? 'active' : '' }}" href="/admin/teams">Tim Kerja</a>
<a class="{{ request()->is('admin/regions*') ? 'active' : '' }}" href="/admin/regions">Wilayah</a>
<div class="nav-label">Monitoring</div>
<a class="{{ request()->is('admin/monitoring/surveys*') ? 'active' : '' }}" href="/admin/monitoring/surveys">Monitoring Survei</a>
<a class="{{ request()->is('admin/monitoring/wilayah*') ? 'active' : '' }}" href="/admin/monitoring/wilayah">Peta Wilayah</a>
<a class="{{ request()->is('admin/monitoring/kinerja*') ? 'active' : '' }}" href="/admin/monitoring/kinerja">Kinerja Mitra</a>
<div class="nav-label">Sistem</div>
<a class="{{ request()->is('admin/logs*') ? 'active' : '' }}" href="/admin/logs">Log Aktivitas</a>
