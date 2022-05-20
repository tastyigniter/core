<?php

namespace Igniter\Flame\View\Compilers;

use Igniter\Flame\Support\Str;
use Illuminate\View\Compilers\ComponentTagCompiler as IlluminateComponentTagCompiler;
use Illuminate\View\ViewFinderInterface;

class ComponentTagCompiler extends IlluminateComponentTagCompiler
{
    public function guessViewName($name, $prefix = '_components.')
    {
        if (!Str::startsWith($prefix, '_'))
            $prefix = '_'.$prefix;

        if (!Str::endsWith($prefix, '.'))
            $prefix .= '.';

        $delimiter = ViewFinderInterface::HINT_PATH_DELIMITER;

        if (Str::contains($name, $delimiter)) {
            return Str::replaceFirst($delimiter, $delimiter.$prefix, $name);
        }

        return $prefix.$name;
    }

    /**
     * Guess the class name for the given component.
     *
     * @param string $component
     * @return string
     */
    public function guessClassName(string $component)
    {
//        $namespace = Container::getInstance()
//            ->make(Application::class)
//            ->getNamespace();

        $class = $this->formatClassName($component);

        return 'Igniter\\Admin\\View\\Components\\'.$class;
    }
}
