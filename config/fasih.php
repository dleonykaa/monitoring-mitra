<?php

/*
|--------------------------------------------------------------------------
| Pengelompokan status FASIH
|--------------------------------------------------------------------------
|
| Kolom statusBreakdown pada file hasil scraping FASIH berisi status seperti
| "APPROVED BY Pengawas:127 | DRAFT:22". Status di bawah dijumlahkan ke dalam
| kelompok Open, Draft, dan Submit sehingga Open + Draft + Submit selalu sama
| dengan totalRegion. Baris dengan status yang tidak terdaftar di sini, atau
| yang jumlahnya tidak sama dengan totalRegion, ditolak saat impor.
| Perubahan pengelompokan berlaku untuk file yang diimpor setelahnya.
|
*/

return [
    'status_groups' => [
        'open' => ['OPEN'],
        'draft' => ['DRAFT', 'REJECTED BY Pengawas'],
        'submit' => [
            'SUBMITTED BY Pencacah',
            'APPROVED BY Pengawas',
            'EDITED BY Admin Kabupaten',
            'EDITED BY Pengawas',
            'REVOKED BY Pengawas',
            'SUBMITTED RESPONDENT',
        ],
    ],
];
