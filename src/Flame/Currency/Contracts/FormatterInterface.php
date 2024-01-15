<?php

namespace Igniter\Flame\Currency\Contracts;

interface FormatterInterface
{
    /**
     * Format the value into the desired currency.
     *
     * @param float $value
     * @param ?string $code
     *
     * @return string
     */
    public function format(float $value, ?string $code = null): string;
}
