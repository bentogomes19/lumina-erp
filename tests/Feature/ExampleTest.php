<?php

namespace Tests\Feature;

/* use Illuminate\Foundation\Testing\RefreshDatabase; */
use Tests\TestCase;

class ExampleTest extends TestCase {

    /**
     * Verifica se a página inicial encaminha ao login administrativo.
     *
     * @return void
     */
    public function test_the_application_returns_a_successful_response(): void {
        $response = $this->get('/');

        $response->assertRedirect(route('filament.lumina.auth.login'));
    }
}
