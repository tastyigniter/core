<?php

namespace Igniter\Admin\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Support\HtmlString getBlock(string $name, ?string $default = null)
 * @method static void appendBlock(string $name, string $contents)
 * @method static void setBlock(string $name, string $contents)
 * @method static string|null getTitle()
 * @method static string|null getHeading()
 * @method static string getButtonList()
 * @method static void setTitle(string $title)
 * @method static void setHeading(string $heading)
 * @method static void setButton(string $name, array $attributes = [])
 * @method static \Illuminate\Support\HtmlString renderHook(string $name)
 * @method static void registerHook(string $name, \Closure $callback)
 * @method static string renderStaticCss()
 *
 * @see \Igniter\Admin\Classes\Template
 */
class Template extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     * @see \Igniter\System\Libraries\Template
     */
    protected static function getFacadeAccessor()
    {
        return 'admin.template';
    }
}
