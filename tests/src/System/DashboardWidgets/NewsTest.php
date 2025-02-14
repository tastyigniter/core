<?php

namespace Igniter\Tests\System\DashboardWidgets;

use DOMDocument;
use Igniter\Admin\Http\Controllers\Dashboard;
use Igniter\System\DashboardWidgets\News;

beforeEach(function() {
    app()->instance(DOMDocument::class, $dom = $this->createMock(DOMDocument::class));
    $dom->method('load')->willReturn(false);
    $this->newsWidget = new News(resolve(Dashboard::class), []);
});

it('renders news widget successfully', function() {
    expect($this->newsWidget->render())->toBeString();
});

it('defines widget properties correctly', function() {
    $properties = $this->newsWidget->defineProperties();

    expect($properties)->toBe([
        'title' => [
            'label' => 'igniter::admin.dashboard.label_widget_title',
            'default' => 'igniter::admin.dashboard.text_news',
        ],
        'newsCount' => [
            'label' => 'igniter::admin.dashboard.text_news_count',
            'default' => 6,
            'type' => 'select',
            'options' => range(1, 10),
            'validationRule' => 'required|integer',
        ],
    ]);
});

it('loads feed items successfully', function() {
    $this->newsWidget->render();

    expect($this->newsWidget->vars['newsFeed'])->toBeArray()
        ->and(count($this->newsWidget->vars['newsFeed']))->toBeLessThanOrEqual(6);
});

it('handles invalid RSS feed URL', function() {
    $this->newsWidget->newsRss = 'https://invalid-url.com/feed';

    $this->newsWidget->render();

    expect($this->newsWidget->vars['newsFeed'])->toBe([]);
});
