<?php

namespace Database\Seeders;

use App\Models\District;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        $districts = [
            'Kepulauan Seribu Utara' => ['Pulau Panggang', 'Pulau Kelapa', 'Pulau Harapan'],
            'Kepulauan Seribu Selatan' => ['Pulau Tidung', 'Pulau Pari', 'Pulau Untung Jawa'],
        ];

        foreach ($districts as $districtName => $villages) {
            $district = District::query()->firstOrCreate(['name' => $districtName]);

            foreach ($villages as $villageName) {
                $district->villages()->firstOrCreate(['name' => $villageName], ['type' => 'pulau']);
            }
        }
    }
}
