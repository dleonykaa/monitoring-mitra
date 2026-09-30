@include('panel.partials.menu', ['menu' => [
    null => [
        ['mitra/dashboard', '/mitra/dashboard', 'Dashboard', 'dashboard'],
        [['mitra/surveys*', 'mitra/entries*'], '/mitra/surveys', 'Daftar Survei', 'survey'],
        ['mitra/data-entri*', '/mitra/data-entri', 'Data Entri', 'table'],
    ],
]])
