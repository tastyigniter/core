<?php

namespace Igniter\Tests\System\Traits;

use Exception;
use Igniter\Admin\Classes\AdminController;
use Igniter\Flame\Exception\SystemException;
use Igniter\System\Traits\ViewMaker;
use Illuminate\Support\Facades\View;

it('returns correct view path when view exists', function() {
    $viewMaker = new class
    {
        use ViewMaker;
    };

    $result = $viewMaker->getViewPath('tests.admin::test', null);
    expect($result)->toEndWith('/views/test.blade.php');
});

it('returns correct view path when view is index', function() {
    $viewMaker = new class
    {
        use ViewMaker;
    };

    $result = $viewMaker->getViewPath('tests.admin::testcontroller', null);
    expect($result)->toEndWith('/views/testcontroller/index.blade.php');
});

it('returns correct view path when unable to guess view name', function() {
    View::shouldReceive('exists')->with('test')->andReturn(false, false, true);
    View::shouldReceive('exists')->with('test.index')->andReturn(false);
    View::shouldReceive('getFinder->find')->with('test')->andReturn('/path/to/test.blade.php');
    $viewMaker = new class
    {
        use ViewMaker;
    };

    $result = $viewMaker->getViewPath('test', null);
    expect($result)->toEndWith('/path/to/test.blade.php');
});

it('returns correct view name when view exists', function() {
    $viewMaker = new class
    {
        use ViewMaker;
    };

    $result = $viewMaker->getViewName('tests.admin::test');
    expect($result)->toBe('tests.admin::test');
});

it('returns correct view name when view is index', function() {
    $viewMaker = new class
    {
        use ViewMaker;
    };

    $result = $viewMaker->getViewName('tests.admin::testcontroller', null);
    expect($result)->toBe('tests.admin::testcontroller.index');
});

it('returns correct view name when unable to guess view name', function() {
    View::shouldReceive('exists')->with('test')->andReturn(false, false, true);
    View::shouldReceive('exists')->with('test.index')->andReturn(false);
    View::shouldReceive('getFinder->find')->with('test')->andReturn('/path/to/test.blade.php');
    $viewMaker = new class
    {
        use ViewMaker;
    };

    expect($viewMaker->getViewName('test', null))->toBe('test');
});

it('throws exception when partial view not found', function() {
    View::shouldReceive('exists')->with('_partials.partial.name')->andReturn(false);
    View::shouldReceive('exists')->with('_partials.partial.name.index')->andReturn(false);
    View::shouldReceive('exists')->with('partial.name')->andReturn(false);

    $viewMaker = new class
    {
        use ViewMaker;
    };

    expect(fn() => $viewMaker->makePartial('partial.name'))->toThrow(SystemException::class);
});

it('returns empty string when partial view not found and throw exception is disabled', function() {
    $viewMaker = new class
    {
        use ViewMaker;
    };

    expect($viewMaker->makePartial('partial.name', [], false))->toBe('');
});

it('returns empty string when layout name is empty', function() {
    $viewMaker = new class
    {
        use ViewMaker;
    };

    expect($viewMaker->makeLayout(''))->toBe('');
});

it('renders view and layout correctly', function() {
    $viewMaker = new class
    {
        use ViewMaker;
    };
    $viewMaker->layout = 'tests.admin::layout';

    $result = $viewMaker->makeView('tests.admin::test', ['key' => 'value']);
    expect($result)->toContain('This is a test view content');
});

it('renders view with no layout correctly', function() {
    $viewMaker = new class
    {
        use ViewMaker;
    };
    $viewMaker->layout = '';

    $result = $viewMaker->makeView('tests.admin::test', ['key' => 'value']);
    expect($result)->toContain('This is a test view content');
});

it('renders view content correctly', function() {
    $viewMaker = new class
    {
        use ViewMaker;
    };

    $result = $viewMaker->makeViewContent('test', [
        'key' => 'value',
        'view' => view('tests.admin::_partials.test-partial'),
    ]);
    expect($result)->toBeString();
});

it('renders partial content correctly', function() {
    $viewMaker = new class
    {
        use ViewMaker;
    };
    $viewMaker->controller = new class extends AdminController
    {
        public array $vars = ['key' => 'value'];
    };

    $result = $viewMaker->makePartial('tests.admin::test-partial', ['key' => 'value']);
    expect($result)->toContain('This is a test partial content');
});

it('returns empty string when file path is invalid', function() {
    $viewMaker = new class
    {
        use ViewMaker;
    };

    $result = $viewMaker->makeFileContent('index.php');
    expect($result)->toBe('');
});

it('throws exception when evaluating view contents', function() {
    $viewMaker = new class
    {
        use ViewMaker;
    };

    expect(fn() => $viewMaker->makeFileContent(__DIR__.'/../../../resources/views/testcontroller/view-with-exception.blade.php'))
        ->toThrow(Exception::class, 'This is a test exception');
});

it('throws throwable when evaluating view contents', function() {
    $viewMaker = new class
    {
        use ViewMaker;
    };

    expect(fn() => $viewMaker->makeFileContent(__DIR__.'/../../../resources/views/testcontroller/view-with-throwable.blade.php'))
        ->toThrow(Exception::class, 'This is a test error');
});

it('compiles file content if expired', function() {
    $viewMaker = new class
    {
        use ViewMaker;
    };

    $result = $viewMaker->compileFileContent(__DIR__.'/../../../resources/views/test.blade.php');
    expect($result)->toContain('/storage/framework/views');
});
