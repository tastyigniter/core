<?php

declare(strict_types=1);

namespace Igniter\Tests\Fixtures\Events;

use Igniter\Flame\Traits\EventDispatchable;

class TestEvent
{
    use EventDispatchable;

    public static function eventName(): string
    {
        return 'test.event';
    }
}
