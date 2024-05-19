<?php

namespace Tests\Admin\Widgets;

use Igniter\Admin\Widgets\SearchBox;
use Illuminate\View\Factory;
use Tests\Admin\Fixtures\Controllers\TestController;

beforeEach(function() {
    $this->controller = resolve(TestController::class);
    $this->searchBoxWidget = new SearchBox($this->controller);
});

it('renders without errors', function() {
    app()->instance('view', $viewMock = $this->createMock(Factory::class));
    $viewMock->method('exists')->with($this->stringContains('searchbox/searchbox'));

    expect($this->searchBoxWidget->render())->toBeString();
})->throws(\Exception::class);

it('prepares vars without errors', function() {
    $this->searchBoxWidget->prepareVars();

    expect($this->searchBoxWidget->vars)
        ->toBeArray()
        ->toHaveKey('searchBox')
        ->toHaveKey('cssClasses')
        ->toHaveKey('placeholder')
        ->toHaveKey('value');
});

it('submits without errors', function() {
    request()->request->add(['search' => 'test']);

    $eventFired = false;
    $this->searchBoxWidget->bindEvent('search.submit', function() use (&$eventFired) {
        $eventFired = true;
    });

    $result = $this->searchBoxWidget->onSubmit();

    expect($eventFired)->toBeTrue()
        ->and($result)->toBeNull();
});

it('gets active term without errors', function() {
    expect($this->searchBoxWidget->getActiveTerm())->toBe('');

    $this->searchBoxWidget->setActiveTerm('test');

    expect($this->searchBoxWidget->getActiveTerm())->toBe('test');

    $this->searchBoxWidget->putSession('term', 'test2');

    expect($this->searchBoxWidget->getActiveTerm())->toBe('test2');
});

it('sets active term without errors', function() {
    $this->searchBoxWidget->setActiveTerm('test');

    expect($this->searchBoxWidget->getActiveTerm())->toBe('test');
});

it('gets name without errors', function() {
    expect($this->searchBoxWidget->getName())->toBe($this->searchBoxWidget->alias);
});