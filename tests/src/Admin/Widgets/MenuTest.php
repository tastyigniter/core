<?php

namespace Tests\Admin\Widgets;

use Igniter\Admin\Classes\MainMenuItem;
use Igniter\Admin\Widgets\Menu;
use Igniter\System\Facades\Assets;
use Igniter\User\Facades\AdminAuth;
use Igniter\User\Models\User;
use Illuminate\View\Factory;
use Tests\Admin\Fixtures\Controllers\TestController;

beforeEach(function () {
    $this->controller = resolve(TestController::class);
    $this->menuWidget = new Menu($this->controller, [
        'items' => [
            'item1' => [
                'path' => 'tests.admin::_partials.test-partial',
            ],
            'item2' => MainMenuItem::link('item2'),
        ],
        'context' => 'test-context',
    ]);
    $this->menuWidget->bindToController();
});

it('renders correctly', function () {
    app()->instance('view', $viewMock = $this->createMock(Factory::class));
    $viewMock->method('exists')->with($this->stringContains('menu/top_menu'));

    expect($this->menuWidget->render())->toBeString();
})->throws(\Exception::class);

it('loads assets correctly', function () {
    Assets::shouldReceive('addJs')->once()->with('mainmenu.js', 'mainmenu-js');
    Assets::shouldReceive('addJs')->once()->with('formwidgets/recordeditor.modal.js', 'recordeditor-modal-js');

    $this->menuWidget->assetPath = [];

    $this->menuWidget->loadAssets();
});

it('renders item element', function () {
    $item = $this->menuWidget->getItem('item1');

    app()->instance('view', $viewMock = $this->createMock(Factory::class));

    $viewMock->method('exists')->with($this->stringContains('menu/item_'.$item->type));

    $this->expectException(\Exception::class);

    expect($this->menuWidget->renderItemElement($item))->toBeString();
});

it('adds items correctly', function () {
    $items = [
        new MainMenuItem('item3', 'Item 3'),
        new MainMenuItem('item4', 'Item 4'),
    ];

    $this->menuWidget->addItems($items);

    expect($this->menuWidget->getItems())->toHaveCount(4);
});

it('gets logged user correctly', function () {
    expect($this->menuWidget->getLoggedUser())->toBeNull();

    $user = User::factory()->create();

    AdminAuth::shouldReceive('check')->andReturnTrue();
    $this->controller->setUser($user);

    expect($this->menuWidget->getLoggedUser())->toBe($user);
});

it('handles onGetDropdownOptions method', function () {
    request()->query->add(['item' => 'item1']);

    expect($this->menuWidget->onGetDropdownOptions())->toBeArray();
});

it('gets context', function () {
    expect($this->menuWidget->getContext())->toBe('test-context');
});
