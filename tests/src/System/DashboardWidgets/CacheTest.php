<?php

namespace Igniter\Tests\System\DashboardWidgets;

use Facades\Igniter\System\Helpers\CacheHelper;
use Igniter\Admin\Http\Controllers\Dashboard;
use Igniter\System\DashboardWidgets\Cache;

beforeEach(function() {
    $this->cacheWidget = new Cache(resolve(Dashboard::class), []);
});

it('renders cache widget successfully', function() {
    expect($this->cacheWidget->render())->toBeString();
});

it('defines widget properties correctly', function() {
    $properties = $this->cacheWidget->defineProperties();

    expect($properties)->toBe([
        'title' => [
            'label' => 'igniter::admin.dashboard.label_widget_title',
            'default' => 'igniter::admin.dashboard.text_cache_usage',
            'type' => 'text',
        ],
    ]);
});

it('clears cache successfully', function() {
    CacheHelper::shouldReceive('clear')->once();

    $result = $this->cacheWidget->onClearCache();

    expect($result)->toHaveKey('#'.$this->cacheWidget->getId());
});
