<?php

namespace Igniter\Tests\Flame\Currency\Drivers;

use DateTime;
use Igniter\Flame\Currency\Drivers\AbstractDriver;

it('returns configuration value when key exists', function() {
    $driver = new class(['key' => 'value']) extends AbstractDriver
    {
        public function create(array $params): bool {}

        public function all(): array {}

        public function find(string $code, int $active = 1): mixed {}

        public function update(string $code, array $attributes, ?DateTime $timestamp = null): int {}

        public function delete(string $code): int {}

        public function testConfig($key, $default = null)
        {
            return $this->config($key, $default);
        }
    };

    expect($driver->testConfig('key'))->toBe('value')
        ->and($driver->testConfig('non_existing_key', 'default_value'))->toBe('default_value')
        ->and($driver->testConfig('non_existing_key'))->toBeNull();
});
