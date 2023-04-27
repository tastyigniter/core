<?php

namespace Igniter\Flame\Database\Attach\Events;

use Igniter\Flame\Database\Model;

class MediaTagCleared
{
    use \Igniter\Flame\Traits\EventDispatchable;

    protected static $dispatchNamespacedEvent = 'attach.mediaTagCleared';

    public function __construct(public Model $model, public ?string $tag)
    {
    }
}
