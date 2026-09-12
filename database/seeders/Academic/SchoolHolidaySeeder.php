<?php

namespace Database\Seeders\Academic;

use App\Models\SchoolHoliday;
use Database\Seeders\Support\SchoolPopulation;
use Illuminate\Database\Seeder;

class SchoolHolidaySeeder extends Seeder {
    public function run(): void {
        SchoolPopulation::assertEnvironment();
        foreach (SchoolPopulation::years() as $year) {
            foreach (['04-21' => 'Tiradentes', '05-01' => 'Dia do Trabalho', '09-07' => 'Independência', '10-12' => 'Nossa Senhora Aparecida', '11-02' => 'Finados', '11-15' => 'Proclamação da República', '11-20' => 'Consciência Negra'] as $date => $name) {
                SchoolHoliday::firstOrCreate(['school_year_id' => $year->id, 'name' => $name], [
                    'start_date' => "{$year->year}-$date", 'end_date' => "{$year->year}-$date",
                    'type' => 'national_holiday', 'is_active' => true,
                ]);
            }
            SchoolHoliday::firstOrCreate(['school_year_id' => $year->id, 'name' => 'Recesso escolar de julho'], [
                'start_date' => "{$year->year}-07-01", 'end_date' => "{$year->year}-07-31",
                'type' => 'school_recess', 'is_active' => true,
            ]);
        }
    }
}
