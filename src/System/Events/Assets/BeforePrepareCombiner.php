<?php

namespace Igniter\System\Events\Assets;

use Igniter\Flame\Traits\EventDispatchable;
use Igniter\System\Libraries\Assets;

class BeforePrepareCombiner
{
    use EventDispatchable;

    protected static $dispatchNamespacedEvent = 'assets.combiner.beforePrepare';

    public function __construct(public Assets $library, public array $assets)
    {
    }
}
