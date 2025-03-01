<?php

declare(strict_types=1);

namespace Igniter\Tests\System\DashboardWidgets;

use Facades\Igniter\System\Helpers\CacheHelper;
use Igniter\Admin\Http\Controllers\Dashboard;
use Igniter\Flame\Support\Facades\File;
use Igniter\System\DashboardWidgets\Cache;

beforeEach(function() {
    $this->cacheWidget = new Cache(resolve(Dashboard::class), []);
});

it('renders cache widget successfully', function() {
    expect($this->cacheWidget->render())->toBeString();
})->only();

it('skips caches with non existence directory', function() {
    File::partialMock()->shouldReceive('isDirectory')->with(storage_path().'/igniter/combiner')->andReturnFalse();
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
