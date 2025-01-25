<?php

namespace Igniter\Tests\Fixtures\Widgets;

class TestFormWidget extends \Igniter\Admin\Classes\BaseFormWidget
{
    public string $property = 'value';

    public function initialize()
    {
        $this->fillFromConfig([
            'property',
        ]);
    }

    public function getFormField()
    {
        return $this->formField;
    }
}
