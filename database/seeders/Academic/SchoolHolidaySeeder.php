<?php

namespace Database\Seeders\Academic;

use App\Enums\HolidayType;
use App\Models\SchoolHoliday;
use App\Models\SchoolYear;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SchoolHolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $activeYear = SchoolYear::where('is_active', true)->first();
        
        if (!$activeYear) {
            $this->command?->warn('SchoolHolidaySeeder: nenhum ano letivo ativo encontrado.');
            return;
        }

        $year = $activeYear->year;

        // Feriados Nacionais 2025
        $holidays = [
            // Feriados Nacionais
            [
                'name' => 'Ano Novo',
                'start_date' => Carbon::create($year, 1, 1),
                'end_date' => Carbon::create($year, 1, 1),
                'type' => HolidayType::NATIONAL_HOLIDAY,
                'description' => 'Feriado Nacional',
            ],
            [
                'name' => 'Carnaval',
                'start_date' => Carbon::create($year, 2, 24),
                'end_date' => Carbon::create($year, 2, 26),
                'type' => HolidayType::NATIONAL_HOLIDAY,
                'description' => 'Feriado Nacional - Recesso de Carnaval',
            ],
            [
                'name' => 'Sexta-feira Santa',
                'start_date' => Carbon::create($year, 4, 18),
                'end_date' => Carbon::create($year, 4, 18),
                'type' => HolidayType::NATIONAL_HOLIDAY,
                'description' => 'Feriado Nacional',
            ],
            [
                'name' => 'Tiradentes',
                'start_date' => Carbon::create($year, 4, 21),
                'end_date' => Carbon::create($year, 4, 21),
                'type' => HolidayType::NATIONAL_HOLIDAY,
                'description' => 'Feriado Nacional',
            ],
            [
                'name' => 'Dia do Trabalho',
                'start_date' => Carbon::create($year, 5, 1),
                'end_date' => Carbon::create($year, 5, 1),
                'type' => HolidayType::NATIONAL_HOLIDAY,
                'description' => 'Feriado Nacional',
            ],
            [
                'name' => 'Corpus Christi',
                'start_date' => Carbon::create($year, 6, 19),
                'end_date' => Carbon::create($year, 6, 19),
                'type' => HolidayType::NATIONAL_HOLIDAY,
                'description' => 'Feriado Nacional',
            ],
            [
                'name' => 'Independência do Brasil',
                'start_date' => Carbon::create($year, 9, 7),
                'end_date' => Carbon::create($year, 9, 7),
                'type' => HolidayType::NATIONAL_HOLIDAY,
                'description' => 'Feriado Nacional',
            ],
            [
                'name' => 'Nossa Senhora Aparecida',
                'start_date' => Carbon::create($year, 10, 12),
                'end_date' => Carbon::create($year, 10, 12),
                'type' => HolidayType::NATIONAL_HOLIDAY,
                'description' => 'Feriado Nacional',
            ],
            [
                'name' => 'Finados',
                'start_date' => Carbon::create($year, 11, 2),
                'end_date' => Carbon::create($year, 11, 2),
                'type' => HolidayType::NATIONAL_HOLIDAY,
                'description' => 'Feriado Nacional',
            ],
            [
                'name' => 'Proclamação da República',
                'start_date' => Carbon::create($year, 11, 15),
                'end_date' => Carbon::create($year, 11, 15),
                'type' => HolidayType::NATIONAL_HOLIDAY,
                'description' => 'Feriado Nacional',
            ],
            [
                'name' => 'Consciência Negra',
                'start_date' => Carbon::create($year, 11, 20),
                'end_date' => Carbon::create($year, 11, 20),
                'type' => HolidayType::NATIONAL_HOLIDAY,
                'description' => 'Feriado Nacional',
            ],
            [
                'name' => 'Natal',
                'start_date' => Carbon::create($year, 12, 25),
                'end_date' => Carbon::create($year, 12, 25),
                'type' => HolidayType::NATIONAL_HOLIDAY,
                'description' => 'Feriado Nacional',
            ],

            // Recessos Escolares
            [
                'name' => 'Recesso de Julho',
                'start_date' => Carbon::create($year, 7, 14),
                'end_date' => Carbon::create($year, 7, 28),
                'type' => HolidayType::SCHOOL_RECESS,
                'description' => 'Recesso Escolar de Inverno',
            ],
            [
                'name' => 'Recesso de Fim de Ano',
                'start_date' => Carbon::create($year, 12, 16),
                'end_date' => Carbon::create($year, 12, 31),
                'type' => HolidayType::SCHOOL_RECESS,
                'description' => 'Recesso de Fim de Ano Letivo',
            ],

            // Eventos Escolares
            [
                'name' => 'Reunião Pedagógica',
                'start_date' => Carbon::create($year, 3, 15),
                'end_date' => Carbon::create($year, 3, 15),
                'type' => HolidayType::SCHOOL_EVENT,
                'description' => 'Dia de planejamento pedagógico - Sem aulas',
            ],
            [
                'name' => 'Festa Junina',
                'start_date' => Carbon::create($year, 6, 13),
                'end_date' => Carbon::create($year, 6, 14),
                'type' => HolidayType::SCHOOL_EVENT,
                'description' => 'Festa Junina da Escola',
            ],
            [
                'name' => 'Semana da Pátria',
                'start_date' => Carbon::create($year, 9, 5),
                'end_date' => Carbon::create($year, 9, 6),
                'type' => HolidayType::SCHOOL_EVENT,
                'description' => 'Atividades cívicas - Sem aulas regulares',
            ],
        ];

        foreach ($holidays as $holiday) {
            SchoolHoliday::updateOrCreate(
                [
                    'school_year_id' => $activeYear->id,
                    'name' => $holiday['name'],
                    'start_date' => $holiday['start_date'],
                ],
                [
                    'end_date' => $holiday['end_date'],
                    'type' => $holiday['type'],
                    'description' => $holiday['description'],
                    'is_active' => true,
                ]
            );
        }

        $this->command?->info('Feriados e dias não letivos criados com sucesso!');
    }
}
