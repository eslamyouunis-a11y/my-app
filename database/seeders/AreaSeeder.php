<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Governorate;
use Illuminate\Database\Seeder;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'القاهرة' => [
                'مدينة نصر',
                'المعادي',
                'حلوان',
                'شبرا',
                'الزمالك',
            ],
            'الجيزة' => [
                'الدقي',
                'المهندسين',
                '6 أكتوبر',
                'الشيخ زايد',
            ],
            'الإسكندرية' => [
                'سيدي جابر',
                'محرم بك',
                'العجمي',
            ],
        ];

        foreach ($data as $governorateName => $areas) {
            $governorate = Governorate::where('name', $governorateName)->first();

            if (! $governorate) {
                continue;
            }

            foreach ($areas as $areaName) {
                Area::firstOrCreate(
                    [
                        'governorate_id' => $governorate->id,
                        'name' => $areaName,
                    ],
                    [
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
