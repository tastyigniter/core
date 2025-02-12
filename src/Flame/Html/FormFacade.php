<?php

namespace Igniter\Flame\Html;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Support\HtmlString open(array $options = [])
 * @method static string close()
 * @method static string token()
 * @method static \Illuminate\Support\HtmlString input(string $type, string $name, string $value = null, array $options = [])
 * @method static \Illuminate\Support\HtmlString hidden(string $name, string $value = null, array $options = [])
 * @method static string getIdAttribute(string $name, array $attributes)
 * @method static mixed getValueAttribute(string $name, string $value = null)
 * @method static mixed old(string $name)
 * @method static bool oldInputIsEmpty()
 * @method static \Illuminate\Contracts\Session\Session getSessionStore()
 * @method static \Igniter\Flame\Html\FormBuilder setSessionStore(\Illuminate\Contracts\Session\Session $session)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 *
 * @see \Igniter\Flame\Html\FormBuilder
 */
class FormFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'form';
    }
}
