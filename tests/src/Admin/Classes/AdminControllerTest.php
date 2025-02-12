<?php

namespace Igniter\Tests\Admin\Classes;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Classes\BaseWidget;
use Igniter\Admin\Widgets\Menu;
use Igniter\Admin\Widgets\Toolbar;
use Igniter\Flame\Exception\AjaxException;
use Igniter\Flame\Exception\FlashException;
use Igniter\Main\Widgets\MediaManager;
use Igniter\Tests\Fixtures\Controllers\TestController;
use Igniter\User\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

beforeEach(function() {
    $this->user = User::factory()->superUser()->create();
});

it('defines paths correctly', function() {
    $controller = resolve(TestController::class);

    expect('igniter.admin::_layouts')->toBeIn($controller->layoutPath)
        ->and('igniter.tests::_layouts')->toBeIn($controller->layoutPath)
        ->and('igniter.tests::')->not()->toBeIn($controller->layoutPath)
        ->and('igniter.admin::')->toBeIn($controller->viewPath)
        ->and('igniter.tests::testcontroller')->toBeIn($controller->viewPath)
        ->and('igniter.tests::')->toBeIn($controller->viewPath)
        ->and('igniter.admin::_partials')->toBeIn($controller->partialPath)
        ->and('igniter.tests::_partials')->toBeIn($controller->partialPath)
        ->and('igniter.tests::')->not()->toBeIn($controller->partialPath)
        ->and('igniter::models/admin')->toBeIn($controller->configPath)
        ->and('igniter::models/system')->toBeIn($controller->configPath)
        ->and('igniter::models/main')->toBeIn($controller->configPath)
        ->and('igniter.tests::models')->toBeIn($controller->configPath)
        ->and('igniter.tests::')->toBeIn($controller->assetPath)
        ->and('igniter::')->toBeIn($controller->assetPath)
        ->and('igniter::js')->toBeIn($controller->assetPath)
        ->and('igniter::css')->toBeIn($controller->assetPath);
});

it('initializes toolbar, mediamanager and main menu widgets correcty', function() {
    $controller = new class extends AdminController {};
    $this->actingAs($this->user, 'igniter-admin');

    $controller->initialize();

    expect($controller->widgets['toolbar'])->toBeInstanceOf(Toolbar::class)
        ->and($controller->widgets['mediamanager'])->toBeInstanceOf(MediaManager::class)
        ->and($controller->widgets['mainmenu'])->toBeInstanceOf(Menu::class);
});

it('throws exception if user does not have permission', function() {
    $user = User::factory()->create();
    $controller = new class extends AdminController
    {
        protected null|string|array $requiredPermissions = ['Admin.Restricted.Access'];
    };
    $this->actingAs($user, 'igniter-admin');

    expect(fn() => $controller->remap('restrictedAction', ['param1', 'param2']))
        ->toThrow(FlashException::class, lang('igniter::admin.alert_user_restricted'));
});

it('returns event response if beforeResponse event is fired', function() {
    $controller = new class extends AdminController {};
    $controller->bindEvent('controller.beforeResponse', fn() => 'eventResponse');

    $response = $controller->remap('index', ['param1', 'param2']);

    expect($response)->toBe('eventResponse');
});

it('throws exception if action is 404', function() {
    $controller = new class extends AdminController {};

    expect(fn() => $controller->remap('404', ['param1', 'param2']))
        ->toThrow(FlashException::class, sprintf('Method [%s] is not found in the controller [%s]', '404', get_class($controller)));
});

it('throws exception if action is not found', function() {
    $controller = new class extends AdminController {};

    expect(fn() => $controller->remap('nonExistentAction', ['param1', 'param2']))
        ->toThrow(FlashException::class, sprintf('Method [%s] is not found in the controller [%s]', 'nonExistentAction', get_class($controller)));
});

it('processes handler throws exception if widget is not found', function() {
    request()->headers->set('X-Requested-With', 'XMLHttpRequest');
    request()->headers->set('X-IGNITER-REQUEST-HANDLER', 'nonExistentWidget::onAjax');
    $controller = resolve(TestController::class);

    $this->expectException(FlashException::class);
    $this->expectExceptionMessage(sprintf(lang('igniter::admin.alert_widget_not_bound_to_controller'), 'nonExistentWidget'));

    $controller->remap('index');
});

it('processes handler throws exception if widget handler does not exist', function() {
    request()->headers->set('X-Requested-With', 'XMLHttpRequest');
    request()->headers->set('X-IGNITER-REQUEST-HANDLER', 'testWidget::onNonExistentHandler');
    $controller = resolve(TestController::class);
    $widget = new class($controller) extends BaseWidget
    {
        public ?string $alias = 'testWidget';
    };
    $widget->bindToController();

    $this->expectException(FlashException::class);
    $this->expectExceptionMessage(sprintf(lang('igniter::admin.alert_ajax_handler_not_found'), 'testWidget::onNonExistentHandler'));

    $controller->remap('index');
});

it('processes widget handler and returns response', function() {
    request()->headers->set('X-Requested-With', 'XMLHttpRequest');
    request()->headers->set('X-IGNITER-REQUEST-HANDLER', 'testWidget::onHandler');
    $controller = resolve(TestController::class);
    $widget = new class($controller) extends BaseWidget
    {
        public ?string $alias = 'testWidget';

        public function onHandler(): array
        {
            return ['status' => 'success'];
        }
    };
    $widget->bindToController();

    $response = $controller->remap('index');

    expect($response)->toBe(['status' => 'success']);
});

it('processes specific page handler and returns handler response', function() {
    $controller = new class extends AdminController
    {
        public function index_onAjax(): Response
        {
            return response(['status' => 'success']);
        }

        public function index()
        {
            return 'index content';
        }
    };
    request()->request->set('_handler', 'onAjax');

    $response = $controller->remap('index', ['param1', 'param2']);

    expect($response->getContent())->toBe(json_encode(['status' => 'success']));
});

it('processes widget generic handler and returns handler response', function() {
    request()->headers->set('X-Requested-With', 'XMLHttpRequest');
    request()->headers->set('X-IGNITER-REQUEST-HANDLER', 'onHandler');
    $controller = resolve(TestController::class);
    $widget = new class($controller) extends BaseWidget
    {
        public ?string $alias = 'testWidget';

        public function onHandler(): Response
        {
            return response(['status' => 'success']);
        }
    };
    $widget->bindToController();

    $response = $controller->remap('index');

    expect($response->getContent())->toBe(json_encode(['status' => 'success']));
});

it('returns null when no matching generic handler is found in widgets', function() {
    request()->headers->set('X-Requested-With', 'XMLHttpRequest');
    request()->headers->set('X-IGNITER-REQUEST-HANDLER', 'onNonExistentHandler');
    $controller = resolve(TestController::class);
    $widget = new class($controller) extends BaseWidget
    {
        public ?string $alias = 'testWidget';
    };
    $widget->bindToController();

    $response = $controller->remap('index');

    expect($response)->toBeNull();
});

it('processes handler and returns partial response', function() {
    request()->request->set('_handler', 'onAjax');
    request()->headers->set('X-IGNITER-REQUEST-PARTIALS', 'test-partial');
    $controller = resolve(TestController::class);

    $response = $controller->remap('index', ['param1', 'param2']);

    expect($response['test-partial'])->toContain('This is a test partial content');
});

it('processes handler and returns handler redirect response', function() {
    request()->headers->set('X-Requested-With', 'XMLHttpRequest');
    request()->headers->set('X-IGNITER-REQUEST-HANDLER', 'onAjax');
    $controller = new class extends AdminController
    {
        public function onAjax(): RedirectResponse
        {
            return $this->redirect('redirected-url');
        }

        public function index()
        {
            return 'index content';
        }
    };

    $response = $controller->remap('index');

    expect($response['X_IGNITER_REDIRECT'])->toContain('redirected-url');
});

it('processes handler and returns handler flash message response', function() {
    request()->headers->set('X-Requested-With', 'XMLHttpRequest');
    request()->headers->set('X-IGNITER-REQUEST-HANDLER', 'onAjax');
    $controller = new class extends AdminController
    {
        public function onAjax(): string
        {
            flash()->success('Test flash message');

            return 'This is a string response';
        }

        public function index()
        {
            return 'index content';
        }
    };

    $response = $controller->remap('index');

    expect($response['#notification'])->toContain('Test flash message')
        ->and($response['result'])->toContain('This is a string response');
});

it('processes handler and throws validation errors', function() {
    request()->headers->set('X-Requested-With', 'XMLHttpRequest');
    request()->headers->set('X-IGNITER-REQUEST-HANDLER', 'onAjax');
    $controller = new class extends AdminController
    {
        public function onAjax(): string
        {
            throw ValidationException::withMessages(['name' => 'Name is required']);
        }

        public function index()
        {
            return 'index content';
        }
    };

    $this->expectException(AjaxException::class);
    $this->expectExceptionMessage('Name is required');

    $controller->remap('index');
});

it('processes handler and throws mass assignment exception', function() {
    request()->headers->set('X-Requested-With', 'XMLHttpRequest');
    request()->headers->set('X-IGNITER-REQUEST-HANDLER', 'onAjax');
    $controller = new class extends AdminController
    {
        public function onAjax(): string
        {
            throw new \Illuminate\Database\Eloquent\MassAssignmentException('Mass assignment exception');
        }

        public function index()
        {
            return 'index content';
        }
    };

    $this->expectException(FlashException::class);
    $this->expectExceptionMessage('Mass assignment exception');

    $controller->remap('index');
});

it('remaps action and renders controller action default view', function() {
    $controller = new class extends AdminController
    {
        public function test() {}
    };

    $response = $controller->remap('test');

    expect($response)->toBeInstanceOf(\Illuminate\Http\Response::class)
        ->and($response->getContent())->toContain('This is a test view content');
});

it('remaps action and returns response', function() {
    $controller = new class extends AdminController
    {
        public function index()
        {
            return 'index content';
        }
    };

    $response = $controller->remap('index');

    expect($response)->toBeInstanceOf(\Illuminate\Http\Response::class)
        ->and($response->getContent())->toBe('index content');
});
