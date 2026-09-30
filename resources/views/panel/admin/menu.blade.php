@include('panel.partials.menu', ['menu' => [
    null => [
        ['admin/dashboard', '/admin/dashboard', 'Dashboard', 'dashboard'],
        ['admin/monitoring*', '/admin/monitoring/progres', 'Monitoring', 'monitoring'],
        ['admin/surveys*', '/admin/surveys', 'Survei', 'survey'],
        ['admin/entri-papi*', '/admin/entri-papi', 'Data Entri PAPI', 'table'],
        ['admin/mitra*', '/admin/mitra', 'Daftar Mitra', 'people'],
    ],
    'Admin' => [
        ['admin/users*', '/admin/users', 'Manajemen Pengguna', 'user-cog'],
    ],
    'Sistem' => [
        ['admin/logs*', '/admin/logs', 'Log Aktivitas', 'history'],
    ],
]])
