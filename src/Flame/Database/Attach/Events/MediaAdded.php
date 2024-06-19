<?php

namespace Igniter\Flame\Database\Attach\Events;

use Igniter\Flame\Database\Attach\Media;

class MediaAdded
{
    use \Igniter\Flame\Traits\EventDispatchable;

    public function __construct(public Media $media) {}

    public static function eventName(): string
    {
        return 'attach.mediaAdded';
    }
}
