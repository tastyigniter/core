<?php

namespace Igniter\Flame\Currency\Contracts;

use DateTime;

interface DriverInterface
{
    /**
     * Create a new currency.
     *
     * @return bool
     */
    public function create(): bool;

    /**
     * Get all currencies.
     *
     * @return array
     */
    public function all(): array;

    /**
     * Get given currency from storage.
     *
     * @param string $code
     * @param int $active
     *
     * @return mixed
     */
    public function find(string $code, int $active = 1): mixed;

    /**
     * Update given currency.
     */
    public function update(string $code, array $attributes, ?DateTime $timestamp = null): int;

    /**
     * Remove given currency from storage.
     */
    public function delete($code): int;
}
