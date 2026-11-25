<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\DashboardWidgets;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\DashboardWidgets\Statistics;
use Igniter\System\Facades\Assets;
use Igniter\System\Models\MailTemplate;
use Igniter\User\Facades\AdminAuth;
use Igniter\User\Models\User;
use Igniter\User\Models\UserRole;

beforeEach(function() {
    Statistics::registerCards(fn(): array => [
        'sale' => [
            'label' => 'lang:igniter::admin.dashboard.text_total_sale',
            'icon' => ' text-success fa fa-4x fa-line-chart',
            'valueFrom' => fn(): string => '£0.00',
        ],
    ]);
    $this->controller = $this->createMock(AdminController::class);
    $this->statistics = new Statistics($this->controller, ['card' => 'sale']);
});

it('tests defineProperties', function() {
    $properties = $this->statistics->defineProperties();

    expect($properties)->toBeArray()->toHaveKey('card');
});

it('tests getActiveCard', function() {
    $card = $this->statistics->getActiveCard();

    expect($card)->toBe('sale');
});

it('tests loadAssets', function() {
    Assets::shouldReceive('addCss')->once()->with('statistics.css', 'statistics-css');

    $this->statistics->assetPath = [];

    // Call the loadAssets method
    $this->statistics->loadAssets();
});

it('tests prepareVars', function() {
    $this->statistics->render();

    expect($this->statistics->vars['statsContext'])->toBe('sale')
        ->and($this->statistics->vars['statsLabel'])->toBe('lang:igniter::admin.dashboard.text_total_sale')
        ->and($this->statistics->vars['statsColor'])->toBe('success')
        ->and($this->statistics->vars['statsIcon'])->toBe(' text-success fa fa-4x fa-line-chart');
});

it('tests getValue', function() {
    Statistics::registerCards(fn(): array => [
        'test-sale' => [
            'label' => 'lang:igniter::admin.dashboard.text_total_sale',
            'icon' => ' text-success fa fa-4x fa-line-chart',
            'valueFrom' => function($cardCode, $start, $end, $callback): string {
                $callback(MailTemplate::query());

                return '£100.00';
            },
        ],
    ]);

    $this->statistics->setProperty('card', 'test-sale');
    $this->statistics->render();

    expect($this->statistics->vars['statsCount'])->toBe('£100.00');
});

it('renders widget with no errors', function() {
    $widget = new Statistics($this->controller, ['card' => 'sale']);

    $widget->render();

    expect($widget->vars['statsContext'])->toEqual('sale')
        ->and($widget->vars['statsLabel'])->toBe('lang:igniter::admin.dashboard.text_total_sale')
        ->and($widget->vars['statsIcon'])->toBe(' text-success fa fa-4x fa-line-chart')
        ->and($widget->vars['statsCount'])->toBe('£0.00');
});

function registerRestrictedStatisticsCard(): void
{
    Statistics::registerCards(fn(): array => [
        'restricted_sale' => [
            'label' => 'Restricted sale',
            'icon' => 'fa fa-lock',
            'permission' => 'Admin.Orders',
            'valueFrom' => fn(): string => '£999.00',
        ],
    ]);
}

function actingAsStatisticsUser(array $permissions): void
{
    $role = UserRole::factory()->create(['permissions' => $permissions]);
    AdminAuth::shouldReceive('getUser')->andReturn(User::factory()->create(['user_role_id' => $role->getKey()]));
}

it('hides the value of a card the user lacks permission for', function() {
    registerRestrictedStatisticsCard();
    actingAsStatisticsUser(['Admin.Dashboard' => 1]);

    $widget = new Statistics($this->controller, ['card' => 'restricted_sale']);
    $widget->render();

    expect($widget->vars['statsCount'])->toBe('--')
        ->and($widget->vars['statsLabel'])->toBe('Restricted sale');
});

it('shows the value of a card the user has permission for', function() {
    registerRestrictedStatisticsCard();
    actingAsStatisticsUser(['Admin.Orders' => 1]);

    $widget = new Statistics($this->controller, ['card' => 'restricted_sale']);
    $widget->render();

    expect($widget->vars['statsCount'])->toBe('£999.00');
});

it('lists only permitted cards as options', function() {
    registerRestrictedStatisticsCard();
    actingAsStatisticsUser(['Admin.Dashboard' => 1]);

    $options = $this->statistics->defineProperties()['card']['options'];

    expect($options)->toHaveKey('sale')
        ->not->toHaveKey('restricted_sale');
});

it('shows cards without a permission to every staff user', function() {
    actingAsStatisticsUser([]);

    $this->statistics->render();

    expect($this->statistics->vars['statsCount'])->toBe('£0.00');
});
