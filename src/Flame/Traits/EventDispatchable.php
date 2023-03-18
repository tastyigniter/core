<?php

namespace Igniter\Flame\Traits;

trait EventDispatchable
{
    public static function dispatchOnce()
    {
        return static::dispatchEvent(func_get_args(), true);
    }

    public static function dispatch(): array|null
    {
        return static::dispatchEvent(func_get_args());
    }

    public static function dispatchIf($boolean, ...$arguments): array|null
    {
        if ($boolean) {
            return static::dispatchEvent($arguments);
        }
    }

    public static function dispatchUnless($boolean, ...$arguments): array|null
    {
        if (!$boolean) {
            return static::dispatchEvent($arguments);
        }
    }

    public static function broadcast()
    {
        return broadcast(new static(...func_get_args()));
    }

    protected static function dispatchEvent($arguments, $halt = false)
    {
        $result = [];

        if (isset(static::$dispatchNamespacedEvent)) {
            $result = event(static::$dispatchNamespacedEvent, $arguments, $halt);
            if ($halt && !is_null($result))
                return $result;
        }

        if (!is_null($response = event(new static(...$arguments), [], $halt))) {
            if ($halt)
                return $response;

            $result = array_merge($result, $response);
        }

        return $halt ? null : $result;
    }
}
