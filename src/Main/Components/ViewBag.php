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
     * @param array $properties
     *
     * @return array
     */
    public function validateProperties(array $properties)
    {
        return $properties;
    }

    /**
     * Implements the getter functionality.
     *
     * @param  string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->properties)) {
            return $this->properties[$name];
        }

        return null;
    }

    /**
     * Determine if an attribute exists on the object.
     *
     * @param  string $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        if (array_key_exists($key, $this->properties)) {
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public function defineProperties()
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
