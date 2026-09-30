<?php

namespace Database\Seeders;

use App\Models\District;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    /**
     * Kecamatan dan desa/kelurahan beserta kode wilayah BPS.
     *
     * @var array<string, array{code: string, villages: array<string, string>}>
     */
    private const DISTRICTS = [
        'Kepulauan Seribu Utara' => [
            'code' => '3101020',
            'villages' => [
                '3101020001' => 'Pulau Panggang',
                '3101020002' => 'Pulau Kelapa',
                '3101020003' => 'Pulau Harapan',
            ],
        ],
        'Kepulauan Seribu Selatan' => [
            'code' => '3101010',
            'villages' => [
                '3101010001' => 'Pulau Tidung',
                '3101010002' => 'Pulau Pari',
                '3101010003' => 'Pulau Untung Jawa',
            ],
        ],
    ];

    public function run(): void
    {
        foreach (self::DISTRICTS as $districtName => $districtData) {
            $district = District::query()->firstOrCreate(['name' => $districtName]);
            $district->update(['code' => $districtData['code']]);

            foreach ($districtData['villages'] as $villageCode => $villageName) {
                $village = $district->villages()->firstOrCreate(['name' => $villageName], ['type' => 'pulau']);
                $village->update(['code' => $villageCode]);
            }
        }
    }
}
