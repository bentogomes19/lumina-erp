<?php

namespace Database\Seeders;

use App\Enums\TeacherStatus;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Str;

class TeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('pt_BR');

        $statusFix = TeacherStatus::ACTIVE->value;    // "active"
        $inactive  = TeacherStatus::INACTIVE->value;  // "inactive"

        $base = [
            [
                'employee_number' => 'PROF-0001',
                'name'            => 'Ana Paula Martins',
                'qualification'   => 'Licenciatura em Letras (Português/Inglês)',
                'hire_date'       => '2021-02-10',
                'email'           => 'ana.martins@escola.test',
                'phone'           => '(11) 3222-1101',
                'bio'             => 'Experiência com BNCC e avaliação formativa.',
                'status'          => $statusFix,
            ],
            [
                'employee_number' => 'PROF-0002',
                'name'            => 'Bruno Henrique Souza',
                'qualification'   => 'Licenciatura em Matemática',
                'hire_date'       => '2020-03-15',
                'email'           => 'bruno.souza@escola.test',
                'phone'           => '(11) 3222-1102',
                'bio'             => 'Foco em resolução de problemas e pensamento algébrico.',
                'status'          => $statusFix,
            ],
            [
                'employee_number' => 'PROF-0003',
                'name'            => 'Carla Renata Dias',
                'qualification'   => 'Licenciatura em Ciências Biológicas',
                'hire_date'       => '2019-02-01',
                'email'           => 'carla.dias@escola.test',
                'phone'           => '(11) 3222-1103',
                'bio'             => 'Projetos interdisciplinares em Ciências da Natureza.',
                'status'          => $statusFix,
            ],
            [
                'employee_number' => 'PROF-0004',
                'name'            => 'Diego Almeida',
                'qualification'   => 'Licenciatura em História',
                'hire_date'       => '2018-02-01',
                'email'           => 'diego.almeida@escola.test',
                'phone'           => '(11) 3222-1104',
                'bio'             => 'Metodologias ativas e cultura digital em sala.',
                'status'          => $statusFix,
            ],
            [
                'employee_number' => 'PROF-0005',
                'name'            => 'Elisa Moreira',
                'qualification'   => 'Licenciatura em Educação Física',
                'hire_date'       => '2022-02-01',
                'email'           => 'elisa.moreira@escola.test',
                'phone'           => '(11) 3222-1105',
                'bio'             => 'Saúde, movimento e inclusão nas práticas esportivas.',
                'status'          => $inactive,
            ],
        ];

        for ($i = 6; $i <= 15; $i++) {
            Teacher::updateOrCreate(
                ['employee_number' => sprintf('PROF-%04d', $i)],
                [
                    'uuid'          => (string) Str::uuid(),
                    'user_id'       => null,
                    'name'          => $faker->name(),
                    'qualification' => $faker->randomElement([
                        'Licenciatura em Letras', 'Licenciatura em Matemática', 'Licenciatura em Geografia',
                        'Licenciatura em Física', 'Licenciatura em Química', 'Licenciatura em Biologia',
                        'Licenciatura em Artes', 'Licenciatura em Educação Física',
                    ]),
                    'hire_date'     => $faker->dateTimeBetween('-6 years', '-1 month'),
                    'email'         => "prof{$i}@escola.test",
                    'phone'         => $faker->phoneNumber(),
                    'bio'           => $faker->sentence(8),
                    'status'        => $faker->randomElement([
                        TeacherStatus::ACTIVE->value,
                        TeacherStatus::INACTIVE->value,
                    ]),
                ]
            );
        }

        for ($i = 6; $i <= 15; $i++) {
            $name  = $faker->name;
            $email = 'prof'.$i.'@escola.test';

            Teacher::updateOrCreate(
                ['employee_number' => sprintf('PROF-%04d', $i)],
                [
                    'uuid'            => (string) Str::uuid(),
                    'user_id'         => null,
                    'name'            => $name,
                    'qualification'   => $faker->randomElement([
                        'Licenciatura em Letras',
                        'Licenciatura em Matemática',
                        'Licenciatura em Geografia',
                        'Licenciatura em Física',
                        'Licenciatura em Química',
                        'Licenciatura em Biologia',
                        'Licenciatura em Artes',
                        'Licenciatura em Educação Física',
                    ]),
                    'hire_date'       => $faker->dateTimeBetween('-6 years', '-1 month'),
                    'email'           => $email,
                    'phone'           => $faker->phoneNumber(),
                    'bio'             => $faker->sentence(8),
                    'status'          => $faker->randomElement([$statusFix, $inactive]),
                ]
            );
        }
    }
}
