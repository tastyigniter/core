<?php

namespace Igniter\Tests\Fixtures\Events;

class TestEvent
{
    use \Igniter\Flame\Traits\EventDispatchable;

    public function __construct($data) {}

    public static function eventName(): string
    {
        return 'test.event';
    }
}
