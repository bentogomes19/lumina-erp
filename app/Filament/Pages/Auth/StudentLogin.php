<?php

namespace App\Filament\Pages\Auth;

use App\Filament\Pages\Auth\Concerns\RateLimitsLoginAttempts;

class StudentLogin extends \Caresome\FilamentAuthDesigner\Pages\Auth\Login {
    use RateLimitsLoginAttempts;
}
