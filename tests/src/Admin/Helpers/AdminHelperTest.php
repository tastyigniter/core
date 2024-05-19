<?php

namespace Tests\Admin\Helpers;

use Igniter\Admin\Helpers\AdminHelper;
use Igniter\Flame\Exception\SystemException;
use Illuminate\Http\RedirectResponse;

beforeEach(function() {
    //    URL::shouldReceive('to')->andReturn('mocked_url');
    //    Request::shouldReceive('getBaseUrl')->andReturn('mocked_base_url');
    //    Request::shouldReceive('ajax')->andReturn(false);
    //    Request::shouldReceive('header')->andReturnNull();
    //    Request::shouldReceive('setUserResolver')->andReturnNull();
    //    Redirect::shouldReceive('to')->andReturn('mocked_redirect');
    //    Redirect::shouldReceive('guest')->andReturn('mocked_guest_redirect');
    //    Redirect::shouldReceive('intended')->andReturn('mocked_intended_redirect');
});

it('returns the admin URI segment', function() {
    expect(AdminHelper::uri())->toBeString()->toEqual('/admin');
});

it('generates an absolute URL in context of the Admin', function() {
    expect(AdminHelper::url('path', [], false))->toBeString()->toEqual('http://localhost/admin/path');
});

it('returns the base admin URL from which this request is executed', function() {
    expect(AdminHelper::baseUrl('path'))->toBeString()->toEqual('//admin/path');
});

it('creates a new redirect response to a given admin path', function() {
    $redirect = AdminHelper::redirect('path', 302, [], false);
    expect($redirect)->toBeInstanceOf(RedirectResponse::class)
        ->and($redirect->getTargetUrl())->toEqual('http://localhost/admin/path');
});

it('creates a new admin redirect response, while putting the current URL in the session', function() {
    expect(AdminHelper::redirectGuest('path', 302, [], false))->toBeInstanceOf(RedirectResponse::class);
});

it('creates a new redirect response to the previously intended admin location', function() {
    expect(AdminHelper::redirectIntended('path', 302, [], false))->toBeInstanceOf(RedirectResponse::class);
});

it('checks if the request has an AJAX handler', function() {
    expect(AdminHelper::hasAjaxHandler())->toBeBool()->toBeFalse();
});

it('returns the AJAX handler for the current request, if available', function() {
    expect(AdminHelper::getAjaxHandler())->toBeNull();
});

it('validates AJAX handler partials', function() {
    request()->headers->set('X-IGNITER-REQUEST-PARTIALS', ['invalid partial']);

    expect(AdminHelper::validateAjaxHandlerPartials())->toBeArray();
})->throws(SystemException::class);

it('returns ajax handler from request header', function() {
    request()->headers->set('X-Requested-With', 'XMLHttpRequest');
    request()->headers->set('X-IGNITER-REQUEST-HANDLER', 'onTest');

    $handler = AdminHelper::getAjaxHandler();

    expect($handler)->toEqual('onTest');
});

it('validates ajax handler with no errors', function($handler) {
    expect(AdminHelper::validateAjaxHandler($handler))->toBeNull();
})->with([
    ['onTest'],
    ['onTestPartial'],
]);

it('validates ajax handler with errors', function($handler) {
    expect(AdminHelper::validateAjaxHandler($handler));
})->with([
    [''],
    ['doTest'],
    ['testPartial'],
])->throws(SystemException::class);