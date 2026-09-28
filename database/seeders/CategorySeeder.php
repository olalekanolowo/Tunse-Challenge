<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Mirrors src/data/challenge/categories.ts's priorityCategories in the frontend repo.
     */
    public function run(): void
    {
        $palette = ['#232332', '#8EBE6E', '#F9AA33', '#EEFFE2', '#BE6E8E'];

        $categories = [
            ['bricklayer', 'Bricklayer', $palette[0]],
            ['plumber', 'Plumber', $palette[1]],
            ['house-painter', 'House Painter', $palette[2]],
            ['carpenter', 'Carpenter/Furniture Maker', $palette[3]],
            ['tiler', 'Tiler', $palette[4]],
            ['fumigation', 'Fumigation', $palette[0]],
            ['home-cleaning', 'Home Cleaning', $palette[1]],
            ['laundry-ironing', 'Laundry & Ironing', $palette[2]],
            ['barber', 'Barber', $palette[3]],
            ['hairstylist', 'Hairstylist', $palette[4]],
            ['electrician', 'Electrician', $palette[0]],
            ['generator-repair', 'Generator Repair', $palette[1]],
            ['mechanic', 'Mechanic', $palette[2]],
            ['tailor', 'Tailor', $palette[3]],
            ['ac-repair', 'AC Repair', $palette[4]],
            ['makeup', 'Makeup', $palette[0]],
            ['solar-installation', 'Solar Installation', $palette[1]],
            ['cctv-installation', 'CCTV Installation', $palette[2]],
        ];

        foreach ($categories as [$code, $label, $bgColor]) {
            Category::updateOrCreate(['code' => $code], ['label' => $label, 'bg_color' => $bgColor]);
        }
    }
}
