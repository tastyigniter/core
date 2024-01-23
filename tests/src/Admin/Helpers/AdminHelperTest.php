<?php

namespace Tests\Admin\Helpers;

use Igniter\Admin\Helpers\AdminHelper;
use Igniter\Flame\Exception\SystemException;

it('returns ajax handler from request header', function () {
    request()->headers->set('X-Requested-With', 'XMLHttpRequest');
    request()->headers->set('X-IGNITER-REQUEST-HANDLER', 'onTest');

    $handler = AdminHelper::getAjaxHandler();

    expect($handler)->toEqual('onTest');
});

it('validates ajax handler with no errors', function ($handler) {
    expect(AdminHelper::validateAjaxHandler($handler))->toBeNull();
})->with([
    ['onTest'],
    ['onTestPartial'],
]);

it('validates ajax handler with errors', function ($handler) {
    expect(AdminHelper::validateAjaxHandler($handler));
})->with([
    [''],
    ['doTest'],
    ['testPartial'],
])->throws(SystemException::class);