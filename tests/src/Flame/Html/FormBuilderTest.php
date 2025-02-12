<?php

namespace Igniter\Tests\Flame\Html;

use BadMethodCallException;
use Igniter\Admin\Http\Controllers\Dashboard;
use Igniter\Flame\Html\FormBuilder;
use Igniter\Flame\Html\FormFacade;
use Igniter\Flame\Html\HtmlBuilder;
use Illuminate\Support\HtmlString;

it('opens form with default method and action', function() {
    $formBuilder = new FormBuilder(new HtmlBuilder, url(), view(), 'csrfToken');
    $form = FormFacade::open(['url' => 'http://example.com']);
    expect($form)->toBeInstanceOf(HtmlString::class)
        ->and((string)$form)->toBe('<form method="POST" action="http://example.com" accept-charset="UTF-8">'
            .'<input name="_token" type="hidden">',
        );

    $form = $formBuilder->open(['action' => Dashboard::class.'@remap']);
    expect($form)->toBeInstanceOf(HtmlString::class)
        ->and((string)$form)->toBe('<form method="POST" action="http://localhost/admin/dashboard" accept-charset="UTF-8">'
            .'<input name="_token" type="hidden" value="csrfToken">',
        );
});

it('opens form with url query option', function() {
    $formBuilder = new FormBuilder(new HtmlBuilder, url(), view(), 'csrfToken');
    $form = $formBuilder->open(['url' => ['admin/dashboard', 'foo', 'bar']]);
    expect($form)->toBeInstanceOf(HtmlString::class)
        ->and((string)$form)->toBe('<form method="POST" action="http://localhost/admin/dashboard/foo/bar" accept-charset="UTF-8">'
            .'<input name="_token" type="hidden" value="csrfToken">',
        );
});

it('opens form with spoofed method', function() {
    $formBuilder = new FormBuilder(new HtmlBuilder, url(), view(), 'csrfToken');
    $form = $formBuilder->open(['method' => 'PUT', 'action' => [Dashboard::class.'@remap', 'slug' => 'test']]);
    expect($form)->toBeInstanceOf(HtmlString::class)
        ->and((string)$form)->toBe('<form method="POST" action="http://localhost/admin/dashboard/test" accept-charset="UTF-8">'
            .'<input name="_method" type="hidden" value="PUT">'
            .'<input name="_token" type="hidden" value="csrfToken">',
        );
});

it('opens form with route option', function() {
    $formBuilder = new FormBuilder(new HtmlBuilder, url(), view(), 'csrfToken');
    $form = $formBuilder->open(['files' => true, 'route' => ['igniter.admin.dashboard', 'slug' => 'test']]);
    expect($form)->toBeInstanceOf(HtmlString::class)
        ->and((string)$form)->toBe('<form method="POST" action="http://localhost/admin/dashboard/test" accept-charset="UTF-8" enctype="multipart/form-data">'
            .'<input name="_token" type="hidden" value="csrfToken">',
        );
});

it('closes form and resets state', function() {
    $formBuilder = new FormBuilder(new HtmlBuilder, url(), view(), 'csrfToken');
    $formBuilder->open(['files' => true, 'route' => 'igniter.admin.dashboard']);
    $form = $formBuilder->close();
    expect($form)->toBeInstanceOf(HtmlString::class)
        ->and((string)$form)->toBe('</form>');
});

it('generates hidden field with csrf token', function() {
    $formBuilder = new FormBuilder(new HtmlBuilder, url(), view(), 'csrfToken');
    $hiddenField = $formBuilder->token();
    expect($hiddenField)->toBeInstanceOf(HtmlString::class)
        ->and((string)$hiddenField)->toBe('<input name="_token" type="hidden" value="csrfToken">')
        ->and($formBuilder->getIdAttribute(null, ['id' => 'inputId']))->toBe('inputId')
        ->and($formBuilder->getValueAttribute(null, 'default'))->toBe('default');
});

it('creates input field with given type, name, and value', function() {
    session()->put('_old_input', ['username' => 'OldUsername']);
    $formBuilder = new FormBuilder(new HtmlBuilder, url(), view(), 'csrfToken');
    $formBuilder->setSessionStore(session()->driver());
    $inputField = $formBuilder->input('text', 'username', 'JohnDoe', ['selected', 'disabled' => true]);
    expect($inputField)->toBeInstanceOf(HtmlString::class)
        ->and((string)$inputField)->toBe('<input selected disabled name="username" type="text" value="OldUsername">');
});

it('creates hidden input field with given name and value', function() {
    $formBuilder = new FormBuilder(new HtmlBuilder, url(), view(), 'csrfToken');
    $hiddenField = $formBuilder->hidden('user_id', '123');
    expect($hiddenField)->toBeInstanceOf(HtmlString::class)
        ->and((string)$hiddenField)->toBe('<input name="user_id" type="hidden" value="123">');
});

it('returns old input value if available', function() {
    session()->put('_old_input', ['username' => ['OldUsername']]);
    $formBuilder = new FormBuilder(new HtmlBuilder, url(), view(), 'csrfToken');
    $formBuilder->setSessionStore(session()->driver());
    expect($formBuilder->getValueAttribute('username'))->toBe('OldUsername')
        ->and($formBuilder->getSessionStore())->not()->toBeNull();
});

it('returns null if old input is empty', function() {
    session()->put('_old_input', ['username' => []]);
    $formBuilder = new FormBuilder(new HtmlBuilder, url(), view(), 'csrfToken');
    $formBuilder->setSessionStore(session()->driver());
    $formBuilder->input('checkbox', 'agree', 'Agree');
    expect($formBuilder->oldInputIsEmpty())->toBeFalse()
        ->and($formBuilder->getValueAttribute('invalid'))->toBeNull();
});

it('returns null when old input is empty and value is null', function() {
    $formBuilder = new FormBuilder(new HtmlBuilder, url(), view(), 'csrfToken', request());
    $formBuilder->setSessionStore(session()->driver());

    request()->merge(['username' => 'Username']);
    $value = $formBuilder->getValueAttribute('username');
    expect($value)->toBe($value);

    view()->share('errors', collect(['username' => 'Username is required']));
    $value = $formBuilder->getValueAttribute('username');
    expect($value)->toBeNull();
});

it('calls macro method if it exists', function() {
    FormBuilder::macro('customMacro', function() {
        return 'macro result';
    });

    $formBuilder = new FormBuilder(new HtmlBuilder, url(), view(), 'csrfToken');
    $result = $formBuilder->customMacro();
    expect($result)->toBe('macro result');
});

it('throws exception if method does not exist', function() {
    $formBuilder = new FormBuilder(new HtmlBuilder, url(), view(), 'csrfToken');
    expect(fn() => $formBuilder->nonExistentMethod())
        ->toThrow(BadMethodCallException::class, 'Method Igniter\Flame\Html\FormBuilder::nonExistentMethod does not exist.');
});
