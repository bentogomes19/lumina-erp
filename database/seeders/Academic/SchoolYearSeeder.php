<?php

namespace Database\Seeders\Academic;

use App\Models\SchoolYear;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SchoolYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $years = [
            [
                'year'      => 2024,
                'starts_at' => Carbon::create(2024, 2, 1),
                'ends_at'   => Carbon::create(2024, 12, 15),
                'is_active' => false,
            ],
            [
                'year'      => 2025,
                'starts_at' => Carbon::create(2025, 2, 1),
                'ends_at'   => Carbon::create(2025, 12, 15),
                'is_active' => true, // ano letivo atual
            ],
        ];

        foreach ($years as $data) {
            SchoolYear::updateOrCreate(
                ['year' => $data['year']],
                $data
            );
        }
    }
}
