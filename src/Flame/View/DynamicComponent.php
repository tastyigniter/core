<?php

namespace Igniter\Flame\View;

use Igniter\Flame\View\Compilers\ComponentTagCompiler;
use Illuminate\Container\Container;
use Illuminate\View\DynamicComponent as IlluminateDynamicComponent;

class DynamicComponent extends IlluminateDynamicComponent
{

    /**
     * The component tag compiler instance.
     *
     * @var \Igniter\Flame\View\Compilers\ComponentTagCompiler
     */
    protected static $compiler;

    /**
     * Get an instance of the Blade tag compiler.
     *
     * @return \Igniter\Flame\View\Compilers\ComponentTagCompiler
     */
    protected function compiler()
    {
        if (!static::$compiler) {
            static::$compiler = new ComponentTagCompiler(
                Container::getInstance()->make('blade.compiler')->getClassComponentAliases(),
                Container::getInstance()->make('blade.compiler')->getClassComponentNamespaces(),
                Container::getInstance()->make('blade.compiler')
            );
        }

        return static::$compiler;
    }
}
