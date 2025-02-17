<?php

declare(strict_types=1);

namespace Igniter\Tests\Fixtures\Events;

class TestEvent
{
    use \Igniter\Flame\Traits\EventDispatchable;

    public static function eventName(): string
    {
        return 'test.event';
    }
}
