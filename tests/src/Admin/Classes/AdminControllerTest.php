<?php

namespace Tests\Admin\Classes;

use Tests\Admin\Fixtures\Controllers\TestController;

it('has defined paths to locate layouts', function() {
    $controller = resolve(TestController::class);

    expect('igniter.admin::_layouts')->toBeIn($controller->layoutPath)
        ->and('tests.admin::_layouts')->toBeIn($controller->layoutPath)
        ->and('tests.admin::')->not()->toBeIn($controller->layoutPath);
});

it('has defined paths to locate views', function() {
    $controller = resolve(TestController::class);

    expect('igniter.admin::')->toBeIn($controller->viewPath)
        ->and('tests.admin::testcontroller')->toBeIn($controller->viewPath)
        ->and('tests.admin::')->toBeIn($controller->viewPath);
});

it('has defined paths to locate partials', function() {
    $controller = resolve(TestController::class);

    expect('igniter.admin::_partials')->toBeIn($controller->partialPath)
        ->and('tests.admin::_partials')->toBeIn($controller->partialPath)
        ->and('tests.admin::')->not()->toBeIn($controller->partialPath);
});

it('has defined paths to locate model config files', function() {
    $controller = resolve(TestController::class);

    expect('igniter::models/admin')->toBeIn($controller->configPath)
        ->and('igniter::models/system')->toBeIn($controller->configPath)
        ->and('igniter::models/main')->toBeIn($controller->configPath)
        ->and('tests.admin::models')->toBeIn($controller->configPath);
});

it('has defined paths to locate asset files', function() {
    $controller = resolve(TestController::class);

    expect('tests.admin::')->toBeIn($controller->assetPath)
        ->and('igniter::')->toBeIn($controller->assetPath)
        ->and('igniter::js')->toBeIn($controller->assetPath)
        ->and('igniter::css')->toBeIn($controller->assetPath);
});

it('can find (default) layout', function() {
    $controller = resolve(TestController::class);
    $viewPath = $controller->getViewPath('default', $controller->layoutPath);

    expect($viewPath)->toEndWith('admin/_layouts/default.blade.php');
});

it('can find (edit) view', function() {
    $controller = resolve(TestController::class);
    $viewPath = $controller->getViewPath('edit', $controller->viewPath);

    expect($viewPath)->toEndWith('admin/edit.blade.php');
});

it('can find (flash) partial', function() {
    $controller = resolve(TestController::class);
    $partialPath = $controller->getViewPath('flash', $controller->partialPath);

    expect($partialPath)->toEndWith('admin/_partials/flash.blade.php');
});

it('can find controller config file', function() {
    $controller = resolve(TestController::class);
    $viewPath = $controller->getConfigPath('status.php');

    expect($viewPath)->toEndWith('models/admin/status.php');
});

it('can find asset file', function() {
    $controller = resolve(TestController::class);

    expect($controller->getAssetPath('app.js'))->toEndWith('js/app.js')
        ->and($controller->getAssetPath('$/igniter/js/vendor.js'))->toEndWith('igniter/js/vendor.js');
});

it('runs the requested controller action', function() {
    $this->get('admin/login')->assertStatus(200);
});

it('runs the requested controller handler', function() {
    $this->post('admin/login', ['_handler' => 'onLogin'])->assertSessionHas('admin_errors');
});
