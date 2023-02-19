<?php

namespace Igniter\Main\Template\Concerns;

use Igniter\Main\Components\ViewBag;

trait HasViewBag
{
    /**
     * @var array Contains the view bag properties.
     * This property is used by the page editor internally.
     */
    public $viewBag = [];

    /**
     * @var mixed Cache store for the getViewBag method.
     */
    protected $viewBagCache = false;

    /**
     * Boot the sortable trait for this model.
     *
     * @return void
     */
    public static function bootHasViewBag()
    {
        static::retrieved(function (self $model) {
            $model->parseSettings();
        });
    }

    public function parseSettings()
    {
        $this->fillViewBagArray();
    }

    /**
     * Returns the configured view bag component.
     * This method is used only in the back-end and for internal system needs when
     * the standard way to access components is not an option.
     * @return \Igniter\Main\Components\ViewBag
     */
    public function getViewBag()
    {
        if ($this->viewBagCache !== false) {
            return $this->viewBagCache;
        }

        $componentName = 'viewBag';
        // Ensure viewBag component has not already been defined on template
        if (!isset($this->settings['components'][$componentName])) {
            $viewBag = new ViewBag(null, []);
            $viewBag->name = $componentName;

            return $this->viewBagCache = $viewBag;
        }

        return $this->viewBagCache = $this->getComponent($componentName);
    }

    /**
     * Copies view bag properties to the view bag array.
     * This is required for the back-end editors.
     * @return void
     */
    protected function fillViewBagArray()
    {
        $viewBag = $this->getViewBag();
        foreach ($viewBag->getProperties() as $name => $value) {
            $this->viewBag[$name] = $value;
        }

        $this->fireEvent('templateModel.fillViewBagArray');
    }
}
