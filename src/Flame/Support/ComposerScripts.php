<?php

declare(strict_types=1);

namespace Igniter\Flame\Support;

use Composer\Script\Event;
use Illuminate\Foundation\Application;

class ComposerScripts
{
    public static function postAutoloadDump(Event $event): void
    {
        require_once $event->getComposer()->getConfig()->get('vendor-dir').'/autoload.php';

        $laravel = new Application(getcwd());

        if (is_file($path = $laravel->make(Igniter::class)->getCachedAddonsPath())) {
            unlink($path);
        }
    }
}
