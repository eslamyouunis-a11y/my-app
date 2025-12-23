<?php

namespace Database\Seeders;

use App\Models\Governorate;
use Illuminate\Database\Seeder;

class GovernorateSeeder extends Seeder
{
    public function run(): void
    {
        $governorates = [
            'القاهرة',
            'الجيزة',
            'الإسكندرية',
            'القليوبية',
            'الشرقية',
            'الدقهلية',
            'الغربية',
            'المنوفية',
            'البحيرة',
            'كفر الشيخ',
            'دمياط',
            'بورسعيد',
            'الإسماعيلية',
            'السويس',
            'الفيوم',
            'بني سويف',
            'المنيا',
            'أسيوط',
            'سوهاج',
            'قنا',
            'الأقصر',
            'أسوان',
            'البحر الأحمر',
            'الوادي الجديد',
            'مطروح',
            'شمال سيناء',
            'جنوب سيناء',
        ];

        foreach ($governorates as $name) {
            Governorate::firstOrCreate(
                ['name' => $name],
                ['is_active' => true]
            );
        }
    }
}
