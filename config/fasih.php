<?php

/*
|--------------------------------------------------------------------------
| Pengelompokan status FASIH
|--------------------------------------------------------------------------
|
| Kolom statusBreakdown pada file hasil scraping FASIH berisi status seperti
| "APPROVED BY Pengawas:127 | DRAFT:22". Status di bawah dijumlahkan ke dalam
| kelompok Open, Draft, dan Submit. Status yang tidak terdaftar di sini
| (mis. EDITED BY Admin Kabupaten, REVOKED BY Pengawas) masuk ke "Lainnya".
| Perubahan pengelompokan berlaku untuk file yang diimpor setelahnya.
|
*/

return [
    'status_groups' => [
        'open' => ['OPEN', 'REJECTED BY Pengawas'],
        'draft' => ['DRAFT'],
        'submit' => ['SUBMITTED BY Pencacah', 'APPROVED BY Pengawas'],
    ],
];
