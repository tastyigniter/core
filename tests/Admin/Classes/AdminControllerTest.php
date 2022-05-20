<?php

namespace Tests\Admin\Classes;

use Tests\Fixtures\Controllers\TestController;

it('should have defined paths', function () {
    $controller = resolve(TestController::class);

    expect('tests.fixtures::testcontroller')->toBeIn($controller->viewPath);
    expect('tests.fixtures::')->toBeIn($controller->viewPath);

    expect('tests.fixtures::_partials.testcontroller')->toBeIn($controller->partialPath);
    expect('tests.fixtures::_partials')->toBeIn($controller->partialPath);

    expect('@/models/admin')->toBeIn($controller->configPath);
    expect('@/models/main')->toBeIn($controller->configPath);
    expect('@/models/system')->toBeIn($controller->configPath);

    expect('@/js')->toBeIn($controller->assetPath);
    expect('@/css')->toBeIn($controller->assetPath);
});

it('should rewrite view path', function () {
    $controller = resolve(TestController::class);
    $view = $controller->guessViewPath('edit', $controller->viewPath);

    expect($view)->toStartWith('igniter.admin::');
});
