<?php

namespace Igniter\Tests\Admin\Widgets;

use Igniter\Admin\Classes\MainMenuItem;
use Igniter\Admin\Models\Status;
use Igniter\Admin\Widgets\Menu;
use Igniter\Flame\Exception\FlashException;
use Igniter\System\Facades\Assets;
use Igniter\Tests\Fixtures\Controllers\TestController;
use Igniter\User\Facades\AdminAuth;
use Igniter\User\MainMenuWidgets\UserPanel;
use Igniter\User\Models\User;

beforeEach(function() {
    $this->controller = resolve(TestController::class);
    $this->controller->setUser(User::factory()->create());
    $this->menuWidget = new Menu($this->controller, [
        'items' => [
            'item1' => [
                'path' => 'tests.admin::_partials.test-partial',
            ],
            'item2' => [
                'path' => 'tests.admin::_partials.test-partial',
                'type' => 'dropdown',
                'options' => [Status::class, 'getDropdownOptionsForOrder'],
            ],
            'item3' => MainMenuItem::dropdown('item3'),
            'item4' => MainMenuItem::widget('user', UserPanel::class),
            'out-of-context' => [
                'path' => 'tests.admin::_partials.test-partial',
                'context' => ['another-context'],
            ],
        ],
        'context' => 'test-context',
    ]);
    $this->menuWidget->bindToController();
});

it('renders correctly', function() {
    expect($this->menuWidget->render())->toBeString();
});

it('loads assets correctly', function() {
    Assets::shouldReceive('addJs')->once()->with('mainmenu.js', 'mainmenu-js');
    Assets::shouldReceive('addJs')->once()->with('formwidgets/recordeditor.modal.js', 'recordeditor-modal-js');

    $this->menuWidget->assetPath = [];

    $this->menuWidget->loadAssets();
});

it('renders item element', function() {
    $item = $this->menuWidget->getItem('item1');

    expect($this->menuWidget->renderItemElement($item))->toBeString();
});

it('adds items correctly', function() {
    $items = [
        new MainMenuItem('item3', 'Item 3'),
        new MainMenuItem('item4', 'Item 4'),
    ];

    $this->menuWidget->addItems($items);

    expect($this->menuWidget->getItems())->toHaveCount(5);
});

it('gets logged user correctly', function() {
    expect($this->menuWidget->getLoggedUser())->toBeNull();

    $user = User::factory()->create();

    AdminAuth::shouldReceive('check')->andReturnTrue();
    $this->controller->setUser($user);

    expect($this->menuWidget->getLoggedUser())->toBe($user);
});

it('handles onGetDropdownOptions method', function() {
    request()->query->add(['item' => 'item2']);

    expect($this->menuWidget->onGetDropdownOptions())->toBeArray();
});

it('onGetDropdownOptions throws exception when missing request data', function() {
    $this->expectException(FlashException::class);
    $this->expectExceptionMessage(lang('igniter::admin.side_menu.alert_invalid_menu'));

    $this->menuWidget->onGetDropdownOptions();
});

it('gets context', function() {
    expect($this->menuWidget->getContext())->toBe('test-context');
});

it('throws exception when retrieving invalid menu item', function() {
    expect(fn() => $this->menuWidget->getItem('invalid-item'))
        ->toThrow(sprintf(lang('igniter::admin.side_menu.alert_no_definition'), 'invalid-item'));
});

it('returns null when making widget with invalid type', function() {
    $item = new MainMenuItem('item1', 'Item 1');
    $item->type = 'invalid';

    expect($this->menuWidget->makeMenuItemWidget($item))->toBeNull();
});
