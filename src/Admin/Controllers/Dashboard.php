<?php

namespace Igniter\Admin\Controllers;

use Igniter\Admin\Facades\AdminAuth;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Facades\Template;
use Igniter\Admin\Widgets\DashboardContainer;
use Illuminate\Support\Facades\Request;

class Dashboard extends \Igniter\Admin\Classes\AdminController
{
    public $containerConfig = [];

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('dashboard');
    }

    public function index()
    {
        if (is_null(Request::segment(2)))
            return $this->redirect('dashboard');

        Template::setTitle(lang('igniter::admin.dashboard.text_title'));
        Template::setHeading(lang('igniter::admin.dashboard.text_heading'));

        $this->initDashboardContainer();

        return $this->makeView('dashboard');
    }

    public function initDashboardContainer()
    {
        $this->containerConfig['canManage'] = array_get($this->containerConfig, 'canManage', $this->canManageWidgets());
        $this->containerConfig['canSetDefault'] = array_get($this->containerConfig, 'canSetDefault', AdminAuth::isSuperUser());
        $this->containerConfig['defaultWidgets'] = array_get($this->containerConfig, 'defaultWidgets', $this->getDefaultWidgets());

        new DashboardContainer($this, $this->containerConfig);
    }

    protected function getDefaultWidgets()
    {
        return [
            'onboarding' => [
                'class' => \Igniter\Admin\DashboardWidgets\Onboarding::class,
                'priority' => 1,
                'config' => [
                    'title' => 'igniter::admin.dashboard.onboarding.title',
                    'width' => '6',
                ],
            ],
            'news' => [
                'class' => \Igniter\System\DashboardWidgets\News::class,
                'priority' => 2,
                'config' => [
                    'title' => 'igniter::admin.dashboard.text_news',
                    'width' => '6',
                ],
            ],
            'order_stats' => [
                'class' => \Igniter\Admin\DashboardWidgets\Statistics::class,
                'priority' => 3,
                'config' => [
                    'context' => 'sale',
                    'width' => '4',
                ],
            ],
            'reservation_stats' => [
                'class' => \Igniter\Admin\DashboardWidgets\Statistics::class,
                'priority' => 4,
                'config' => [
                    'context' => 'lost_sale',
                    'width' => '4',
                ],
            ],
            'customer_stats' => [
                'class' => \Igniter\Admin\DashboardWidgets\Statistics::class,
                'priority' => 5,
                'config' => [
                    'context' => 'cash_payment',
                    'width' => '4',
                ],
            ],
            'charts' => [
                'class' => \Igniter\Admin\DashboardWidgets\Charts::class,
                'priority' => 6,
                'config' => [
                    'title' => 'igniter::admin.dashboard.text_reports_chart',
                    'width' => '12',
                ],
            ],
        ];
    }

    protected function canManageWidgets()
    {
        return $this->getUser()->hasPermission('Admin.Dashboard');
    }
}
