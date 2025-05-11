<?php

declare(strict_types=1);

namespace Igniter\Flame\Html;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string attributes(array $attributes)
 *
 * @see \Igniter\Flame\Html\HtmlBuilder
 */
class HtmlFacade extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'html';
    }
}
