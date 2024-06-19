<?php

namespace Igniter\Flame\Database\Attach\Events;

use Igniter\Flame\Database\Model;

class MediaTagCleared
{
    use \Igniter\Flame\Traits\EventDispatchable;

    public function __construct(public Model $model, public ?string $tag) {}

    public static function eventName(): string
    {
        return 'attach.mediaTagCleared';
    }
}
