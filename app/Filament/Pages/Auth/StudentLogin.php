<?php

namespace App\Filament\Pages\Auth;

use App\Filament\Pages\Auth\Concerns\RateLimitsLoginAttempts;

class StudentLogin extends \Filament\Auth\Pages\Login {
    use RateLimitsLoginAttempts;
}
