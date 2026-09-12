<?php

namespace Database\Seeders\Academic;

use App\Enums\SchoolYearStatus;
use App\Models\SchoolYear;
use App\Models\SchoolYearTerm;
use Database\Seeders\Support\SchoolPopulation;
use Illuminate\Database\Seeder;

class SchoolYearSeeder extends Seeder {
    public function run(): void {
        SchoolPopulation::assertEnvironment();
        foreach (range(SchoolPopulation::firstYear(), now()->year + 1) as $number) {
            $status = match (true) {
                $number < now()->year => SchoolYearStatus::CLOSED,
                $number === now()->year => SchoolYearStatus::ACTIVE,
                default => SchoolYearStatus::PLANNING,
            };
            $year = SchoolYear::updateOrCreate(['year' => $number], [
                'starts_at' => "$number-02-01", 'ends_at' => "$number-12-15", 'status' => $status,
            ]);
            foreach ([1 => ['02-01', '04-30'], 2 => ['05-01', '06-30'], 3 => ['08-01', '09-30'], 4 => ['10-01', '12-15']] as $seq => [$start, $end]) {
                SchoolYearTerm::firstOrCreate(['school_year_id' => $year->id, 'sequence' => $seq], [
                    'name' => "{$seq}º Bimestre", 'type' => 'bimestre',
                    'starts_at' => "$number-$start", 'ends_at' => "$number-$end",
                    'grade_entry_starts_at' => "$number-$start", 'grade_entry_ends_at' => "$number-$end",
                    'grades_published' => $number < now()->year || now()->toDateString() > "$number-$end",
                ]);
            }
        }
    }
}
