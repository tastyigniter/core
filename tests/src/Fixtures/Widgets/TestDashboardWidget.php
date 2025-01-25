<?php

namespace Igniter\Tests\Fixtures\Widgets;

use Igniter\Admin\Classes\BaseDashboardWidget;

class TestDashboardWidget extends BaseDashboardWidget
{
    protected string $defaultAlias = 'test-dashboard-widget';

    public function defineProperties(): array
    {
        return [
            'title' => [
                'label' => 'igniter::admin.dashboard.label_widget_title',
                'default' => 'igniter::admin.dashboard.text_news',
            ],
            'newsCount' => [
                'label' => 'igniter::admin.dashboard.text_news_count',
                'default' => 6,
                'type' => 'repeater',
                'validationRule' => 'required|integer',
            ],
        ];
    }
}
