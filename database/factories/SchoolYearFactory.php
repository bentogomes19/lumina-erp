<?php

namespace Database\Factories;

use App\Enums\SchoolYearStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchoolYearFactory extends Factory {
    public function definition(): array {
        return ['year' => now()->year, 'starts_at' => now()->year . '-02-01', 'ends_at' => now()->year . '-12-15', 'status' => SchoolYearStatus::PLANNING];
    }

    public function forYear(int $year): static {
        return $this->state(fn () => ['year' => $year, 'starts_at' => "$year-02-01", 'ends_at' => "$year-12-15", 'status' => $year < now()->year ? SchoolYearStatus::CLOSED : SchoolYearStatus::PLANNING]);
    }

    public function active(): static { return $this->state(fn () => ['status' => SchoolYearStatus::ACTIVE]); }
}
