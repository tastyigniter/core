<?php

declare(strict_types=1);

namespace Igniter\Tests\Fixtures\Widgets;

use Override;
use Igniter\Admin\Classes\BaseWidget;

class TestWidget extends BaseWidget
{
    public string $property = 'value';

    protected string $defaultAlias = 'testwidget';

    #[Override]
    public function initialize(): void
    {
        $this->fillFromConfig([
            'property',
        ]);
    }

    public function onAjaxTest() {}
}
