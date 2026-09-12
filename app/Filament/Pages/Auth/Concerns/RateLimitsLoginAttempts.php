<?php

namespace App\Filament\Pages\Auth\Concerns;

use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

trait RateLimitsLoginAttempts {

    /**
     * Autentica o usuário aplicando limites independentes por identidade e IP.
     *
     * @return LoginResponse|null
     */
    public function authenticate(): ?LoginResponse {
        try {
            $this->rateLimitIdentity();
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $response = parent::authenticate();

        if ($response) {
            RateLimiter::clear($this->identityRateLimitKey());
            $this->clearRateLimiter('authenticate');
        }

        return $response;
    }

    /**
     * @throws TooManyRequestsException
     */
    private function rateLimitIdentity(): void {
        $key = $this->identityRateLimitKey();

        if (RateLimiter::tooManyAttempts($key, User::MAX_LOGIN_ATTEMPTS)) {
            throw new TooManyRequestsException(
                static::class,
                'authenticate',
                request()->ip(),
                RateLimiter::availableIn($key),
            );
        }

        RateLimiter::hit($key, 60);
    }

    private function identityRateLimitKey(): string {
        $identity = Str::lower(trim((string)($this->data['email'] ?? '')));

        return 'login:identity:'.hash('sha256', $identity);
    }
}
