<?php

namespace Igniter\Main\Events;

use Igniter\Flame\Traits\EventDispatchable;

class ThemeExtendFormConfigEvent
{
    use EventDispatchable;

    public function __construct(public string $themeName, public array $config) {}

    public function getConfig(): array
    {
        return $this->config;
    }
}
