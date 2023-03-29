<?php

namespace Igniter\Main\Components;

use Igniter\System\Classes\BaseComponent;

/**
 * The view bag stores custom template properties.
 * This is a hidden component ignored by the back-end UI.
 */
class ViewBag extends BaseComponent
{
    /**
     * @var bool This component is hidden from the admin UI.
     */
    public $isHidden = true;

    /**
     * @return array
     */
    public function componentDetails()
    {
        return [
            'name' => 'viewBag',
            'description' => 'Stores custom template properties.',
        ];
    }

    /**
     * @return array
     */
    public function validateProperties(array $properties)
    {
        return $properties;
    }

    /**
     * Implements the getter functionality.
     */
    public function __get(string $name): mixed
    {
        return $this->properties[$name] ?? null;
    }

    /**
     * Determine if an attribute exists on the object.
     */
    public function __isset(string $key): bool
    {
        if (array_key_exists($key, $this->properties)) {
            return true;
        }

        return false;
    }

    public function defineProperties(): array
    {
        $result = [];

        foreach ($this->properties as $name => $value) {
            $result[$name] = [
                'title' => $name,
                'type' => 'text',
            ];
        }

        return $result;
    }
}
