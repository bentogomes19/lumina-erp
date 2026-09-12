<?php

namespace Database\Seeders\Users;

use Database\Seeders\Support\SchoolPopulation;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder {
    public function run(): void {
        SchoolPopulation::account('admin@lumina.com', 'Administrador', 'admin');
    }
}
