<?php

namespace Database\Seeders;

use App\Enums\StudentStatus;
use App\Models\Student;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class StudentSeeder extends Seeder
{
    private function nextRegNumber(): string
    {
        // Simples e suficiente para seed: usa o count atual
        $n = Student::count() + 1;
        return 'ALU-' . str_pad((string)$n, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create('pt_BR');

        for ($i = 0; $i < 40; $i++) {
            $name = $faker->name();
            $cpf  = preg_replace('/\D+/', '', $faker->cpf(false));

            Student::updateOrCreate(
                ['cpf' => $cpf],
                [
                    'uuid'                => (string) Str::uuid(),             // <- gerar aqui
                    'registration_number' => $this->nextRegNumber(),           // <- e aqui
                    'name'                => $name,
                    'birth_date'          => $faker->dateTimeBetween('-18 years', '-6 years'),
                    'gender'              => $faker->randomElement(['M','F','O']),
                    'email'               => $faker->unique()->safeEmail(),
                    'phone_number'        => $faker->cellphoneNumber(),
                    'address'             => $faker->streetAddress(),
                    'address_district'    => $faker->streetName(),
                    'city'                => $faker->city(),
                    'state'               => $faker->stateAbbr(),
                    'postal_code'         => $faker->postcode(),
                    'mother_name'         => $faker->name('female'),
                    'father_name'         => $faker->name('male'),
                    'guardian_main'       => $faker->name(),
                    'guardian_phone'      => $faker->cellphoneNumber(),
                    'guardian_email'      => $faker->safeEmail(),
                    'transport_mode'      => $faker->randomElement(['none','car','bus','van','walk','bike']),
                    'has_special_needs'   => $faker->boolean(10),
                    'allergies'           => $faker->boolean(20) ? 'Lactose' : null,
                    'enrollment_date'     => now()->subYears(rand(0, 6))->startOfYear()->addMonths(1),
                    'exit_date'           => null,
                    'photo_url'           => null,
                    'meta'                => [],
                ]
            );
        }
    }
}
