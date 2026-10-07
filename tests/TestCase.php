<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase {
    public function createApplication() {
        $app = parent::createApplication();
        $connection = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$connection}.database");

        if (!($connection === 'sqlite' && $database === ':memory:')
            && !preg_match('/^testing(?:_test_\d+)?$/', (string) $database)) {
            throw new \RuntimeException("Testes bloqueados: a conexão {$connection} aponta para o banco {$database}, fora do ambiente de testes.");
        }

        return $app;
    }
}
