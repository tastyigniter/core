<?php

namespace Igniter\Flame\Database\Attach\Events;

use Igniter\Flame\Database\Attach\Media;

class MediaAdded
{
    use \Igniter\Flame\Traits\EventDispatchable;

    protected static $dispatchNamespacedEvent = 'attach.mediaAdded';

    public function __construct(public Media $media)
    {
    }
}
