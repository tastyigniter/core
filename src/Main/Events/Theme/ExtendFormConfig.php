<?php

namespace Igniter\Main\Events\Theme;

use Igniter\Flame\Traits\EventDispatchable;

class ExtendFormConfig
{
    use EventDispatchable;

    public function __construct(public string $themeName, public array $config)
    {
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}
