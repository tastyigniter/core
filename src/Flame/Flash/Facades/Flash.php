<?php

namespace Igniter\Flame\Flash\Facades;

use Illuminate\Support\Facades\Facade as IlluminateFacade;

/**
 * @method static \Igniter\Flame\Flash\FlashBag setSessionKey(string $key)
 * @method static string getSessionKey()
 * @method static \Illuminate\Support\Collection messages()
 * @method static \Illuminate\Support\Collection all()
 * @method static void set(string|null $level = null, string|null $message = null)
 * @method static \Igniter\Flame\Flash\FlashBag alert(string $message)
 * @method static \Igniter\Flame\Flash\FlashBag info(string $message)
 * @method static \Igniter\Flame\Flash\FlashBag success(string $message)
 * @method static \Igniter\Flame\Flash\FlashBag error(string $message)
 * @method static \Igniter\Flame\Flash\FlashBag danger(string $message)
 * @method static \Igniter\Flame\Flash\FlashBag warning(string $message)
 * @method static \Igniter\Flame\Flash\FlashBag message(\Igniter\Flame\Flash\Message|string|null $message = null, string|null $level = null)
 * @method static \Igniter\Flame\Flash\FlashBag overlay(string|null $message = null, string $title = '')
 * @method static \Igniter\Flame\Flash\FlashBag now()
 * @method static \Igniter\Flame\Flash\FlashBag important()
 * @method static \Igniter\Flame\Flash\FlashBag clear()
 *
 * @see \Igniter\Flame\Flash\FlashBag
 */
class Flash extends IlluminateFacade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     * @see \Igniter\Flame\Flash\FlashBag
     */
    protected static function getFacadeAccessor()
    {
        return 'flash';
    }
}
