<?php

declare(strict_types=1);

namespace Igniter\Tests\Fixtures\Widgets;

use Override;
use Igniter\Admin\Classes\BaseDashboardWidget;

class TestDashboardWidget extends BaseDashboardWidget
{
    protected string $defaultAlias = 'test-dashboard-widget';

    #[Override]
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
