<?php

namespace Igniter\Tests\Fixtures\Widgets;

use Igniter\Admin\Classes\BaseWidget;

class TestWidget extends BaseWidget
{
    public string $property = 'value';

    protected string $defaultAlias = 'testwidget';

    public function initialize()
    {
        $this->fillFromConfig([
            'property',
        ]);
    }

    public function onAjaxTest()
    {

    }
}
