<?php

namespace Igniter\Admin\Classes;

use Igniter\System\Traits\PropertyContainer;

/**
 * Dashboard Widget base class
 * Dashboard widgets are used inside the DashboardContainer.
 */
class BaseDashboardWidget extends BaseWidget
{
    use PropertyContainer;

    public function __construct(AdminController $controller, array $properties = [])
    {
        $this->properties = $this->validateProperties($properties);

        $this->setConfig($properties);

        parent::__construct($controller, $properties);

        $this->fillFromConfig();
    }

    public function getPropertiesToSave()
    {
        return array_except($this->properties, ['startDate', 'endDate']);
    }

    public function getPropertyRules()
    {
        $rules = $attributes = [];
        foreach ($this->defineProperties() as $name => $params) {
            if (strlen($rule = array_get($params, 'validationRule', ''))) {
                $rules[$name] = $rule;
                $attributes[$name] = array_get($params, 'label', $name);
            }
        }

        return [$rules, $attributes];
    }

    public function getWidth()
    {
        return $this->property('width');
    }

    public function getCssClass()
    {
        return $this->property('cssClass');
    }

    public function getPriority()
    {
        return $this->property('priority', 9999);
    }

    public function getStartDate()
    {
        return $this->property('startDate');
    }

    public function getEndDate()
    {
        return $this->property('endDate');
    }
}
