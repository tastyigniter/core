<?php

namespace Igniter\System\Events;

use Igniter\Flame\Traits\EventDispatchable;
use Igniter\System\Libraries\Assets;

class AssetsBeforePrepareCombinerEvent
{
    use EventDispatchable;

    public function __construct(public Assets $library, public array $assets) {}

    public static function eventName(): string
    {
        return 'assets.combiner.beforePrepare';
    }
}
