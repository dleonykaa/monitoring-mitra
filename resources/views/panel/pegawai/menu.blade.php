@include('panel.partials.menu', ['menu' => [
    null => [
        ['pegawai/dashboard', '/pegawai/dashboard', 'Dashboard', 'dashboard'],
        ['pegawai/monitoring*', '/pegawai/monitoring/progres', 'Monitoring', 'monitoring'],
        ['pegawai/surveys*', '/pegawai/surveys', 'Survei', 'survey'],
        ['pegawai/entri-papi*', '/pegawai/entri-papi', 'Data Entri PAPI', 'table'],
    ],
]])
