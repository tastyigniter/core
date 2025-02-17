<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Classes;

use Igniter\System\Classes\ControllerAction;
use Igniter\Tests\Fixtures\Controllers\TestController;
use LogicException;

it('initializes with controller and sets paths', function() {
    $controller = resolve(TestController::class);
    $controller->configPath = ['/path/to/config'];
    $controller->partialPath = ['/path/to/partials'];

    $action = new ControllerAction($controller);

    expect($action->configPath)->toBe(['/path/to/config'])
        ->and($action->partialPath)->toBe(['/path/to/partials']);
});

it('throws exception if required property is missing', function() {
    $controller = resolve(TestController::class);

    expect(fn() => new class($controller) extends ControllerAction
    {
        protected array $requiredProperties = ['missingProperty'];
    })->toThrow(LogicException::class);
});

it('sets and gets config correctly', function() {
    $controller = resolve(TestController::class);
    $action = new ControllerAction($controller);

    $config = [
        'key' => 'value',
        'nested' => ['key' => 'nested-value'],
    ];
    $action->setConfig($config);

    expect($action->getConfig())->toBe($config)
        ->and($action->getConfig('key'))->toBe('value')
        ->and($action->getConfig('nested[key]'))->toBe('nested-value')
        ->and($action->getConfig('nonexistent', 'default'))->toBe('default')
        ->and($action->getConfig('nested[nonexistent]', 'default'))->toBe('default');
});

it('hides action methods correctly', function() {
    $controller = resolve(TestController::class);
    $controller->hiddenActions = [];

    $action = new class($controller) extends ControllerAction
    {
        public function testHideAction(string|array $action): void
        {
            $this->hideAction($action);
        }
    };

    $action->testHideAction('someMethod');

    expect($controller->hiddenActions)->toContain('someMethod');
});
