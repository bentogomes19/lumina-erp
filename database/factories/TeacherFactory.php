<?php

namespace Database\Factories;

use App\Enums\AcademicTitle;
use App\Enums\TeacherRegime;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Teacher>
 */
class TeacherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $academicTitle = $this->faker->randomElement(AcademicTitle::cases());
        $teacherRegime = $this->faker->randomElement(TeacherRegime::cases());

        return [
            'uuid'             => $this->faker->uuid,
            'user_id'          => User::factory(),
            'cpf'              => $this->faker->numerify('###.###.###-##'),
            'employee_number'  => $this->faker->unique()->numerify('P####'),
            'name'             => $this->faker->name,
            'qualification'    => 'Licenciatura em ' . $this->faker->randomElement(['Matemática','História','Língua Portuguesa']),
            'academic_title'   => $academicTitle->value,
            'birth_date'       => $this->faker->date(),
            'gender'           => $this->faker->randomElement(['M','F','O']),
            'hire_date'        => $this->faker->date(),
            'admission_date'   => $this->faker->date(),
            'termination_date' => null,
            'regime'           => $teacherRegime->value,
            'weekly_workload'  => 20,
            'max_classes'      => 10,
            'email'            => $this->faker->unique()->safeEmail,
            'phone'            => $this->faker->phoneNumber,
            'mobile'           => $this->faker->phoneNumber,
            'bio'              => $this->faker->sentence(10),
            'lattes_url'       => null,
            'status'           => 'active',
            'address_street'   => $this->faker->streetAddress,
            'address_number'   => $this->faker->buildingNumber,
            'address_district' => $this->faker->citySuffix,
            'address_city'     => $this->faker->city,
            'address_state'    => $this->faker->randomElement(['SP','RJ','MG','RS', 'PR', 'MA', 'MS', 'SC', 'PR', 'DF']),
            'address_zip'      => $this->faker->numerify('#####-###'),
        ];
    }
}
