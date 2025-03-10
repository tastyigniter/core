<?php

declare(strict_types=1);

namespace Igniter\Tests\Fixtures\Widgets;

use Igniter\Admin\Classes\BaseWidget;

class TestWidget extends BaseWidget
{
    public string $property = 'value';

    protected string $defaultAlias = 'testwidget';

    public function initialize(): void
    {
        $this->fillFromConfig([
            'property',
        ]);
    }

    public function onAjaxTest() {}
}
