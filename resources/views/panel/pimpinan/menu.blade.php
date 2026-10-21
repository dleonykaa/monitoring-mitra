@include('panel.partials.menu', ['menu' => [
    null => [
        ['pimpinan/dashboard', '/pimpinan/dashboard', 'Dashboard', 'dashboard'],
        ['pimpinan/monitoring*', '/pimpinan/monitoring/progres', 'Monitoring', 'monitoring'],
        ['pimpinan/surveys*', '/pimpinan/surveys', 'Survei', 'survey'],
        ['pimpinan/entri-papi*', '/pimpinan/entri-papi', 'Data Entri PAPI', 'table'],
    ],
]])
