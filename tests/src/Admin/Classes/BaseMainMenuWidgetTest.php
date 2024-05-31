<?php

namespace Igniter\Tests\Admin\Classes;

use Igniter\Admin\Classes\BaseMainMenuWidget;
use Igniter\Admin\Classes\MainMenuItem;
use Igniter\Tests\Admin\Fixtures\Controllers\TestController;

it('constructs correctly', function() {
    $widget = new BaseMainMenuWidget(new TestController(), new MainMenuItem('test-menu-item'), []);

    expect($widget)->toBeInstanceOf(BaseMainMenuWidget::class);
});
