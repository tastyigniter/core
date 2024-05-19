<?php

namespace Igniter\Flame\Mixins;

use Illuminate\Support\Str;

/** @mixin \Illuminate\Support\Str */
class StringMixin
{
    /**
     * Converts number to its ordinal English form.
     *
     * This method converts 13 to 13th, 2 to 2nd ...
     */
    public function ordinal()
    {
        return function($number) {
            if (in_array($number % 100, range(11, 13))) {
                return $number.'th';
            }

            return match ($number % 10) {
                1 => $number.'st',
                2 => $number.'nd',
                3 => $number.'rd',
                default => $number.'th',
            };
        };
    }

    /**
     * Converts line breaks to a standard \r\n pattern.
     */
    public function normalizeEol()
    {
        return function($string) {
            return preg_replace('~\R~u', "\r\n", $string);
        };
    }

    /**
     * Removes the starting slash from a class namespace \
     */
    public function normalizeClassName()
    {
        return function($name) {
            if (is_object($name)) {
                $name = get_class($name);
            }

            return '\\'.ltrim($name, '\\');
        };
    }

    /**
     * Generates a class ID from either an object or a string of the class name.
     */
    public function getClassId()
    {
        return function($name) {
            if (is_object($name)) {
                $name = get_class($name);
            }
            $name = ltrim($name, '\\');
            $name = str_replace('\\', '_', $name);

            return strtolower($name);
        };
    }

    /**
     * Returns a class namespace
     */
    public function getClassNamespace()
    {
        return function($name) {
            $name = Str::normalizeClassName($name);

            return substr($name, 0, strrpos($name, '\\'));
        };
    }

    /**
     * If $string begins with any number of consecutive symbols,
     * returns the number, otherwise returns 0
     */
    public function getPrecedingSymbols()
    {
        return function($string, $symbol) {
            return strlen($string) - strlen(ltrim($string, $symbol));
        };
    }
}
