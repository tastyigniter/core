<?php

namespace Igniter\Tests\Admin\Traits;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Traits\ControllerUtils;
use Igniter\Flame\Exception\FlashException;
use Illuminate\Routing\Controller;

beforeEach(function() {
    $this->controller = new class extends AdminController {};
});

it('returns false if action does not exist', function() {
    expect($this->controller->getClass())->toBe($this->controller::class)
        ->and($this->controller->checkAction('nonExistentAction'))->toBeFalse();
});

it('throws exception if action is hidden', function() {
    $controller = new class extends AdminController
    {
        public array $hiddenActions = ['hiddenAction'];

        public function hiddenAction()
        {
            return 'hidden action called';
        }
    };

    expect(fn() => $controller->checkAction('hiddenAction'))->toThrow(FlashException::class);
});

it('calls action method via remap correctly', function() {
    $controller = new class extends AdminController
    {
        public string $action = 'actionMethod';

        public function actionMethod()
        {
            return 'action called';
        }
    };

    $result = $controller->callAction('actionMethod', []);

    expect($result->getContent())->toBe('action called')
        ->and($controller->getAction())->toBe('actionMethod');
});

it('calls action method correctly', function() {
    $controller = new class extends Controller
    {
        use ControllerUtils;

        public function actionMethod()
        {
            return 'action called';
        }
    };

    $result = $controller->callAction('actionMethod', []);

    expect($result)->toBe('action called');
});

it('throws exception if action method is not found', function() {
    expect(fn() => $this->controller->callAction('nonExistentMethod', []))->toThrow(FlashException::class);
});

it('sets and gets property value using magic get', function() {
    $controller = new class extends AdminController
    {
        protected $properties = [];

        public function extendableSet(string $name, mixed $value): void
        {
            $this->properties[$name] = $value;
        }

        public function extendableGet(string $name): mixed
        {
            return $this->properties[$name] ?? null;
        }
    };

    $controller->testProperty = 'testValue';

    expect($controller->testProperty)->toBe('testValue');
});

it('calls method using magic call', function() {
    $controller = new class extends AdminController
    {
        public function extendableCall(string $name, ?array $params = null): string
        {
            if ($name === 'testMethod') {
                return 'method called';
            }
        }
    };

    $result = $controller->testMethod();

    expect($result)->toBe('method called');
});
