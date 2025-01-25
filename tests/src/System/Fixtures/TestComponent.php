<?php

namespace Igniter\Tests\System\Fixtures;

use Illuminate\Support\Facades\Validator;

class TestComponent extends \Igniter\System\Classes\BaseComponent
{
    public static function componentMeta()
    {
        return [
            'code' => 'testComponent',
            'name' => 'Test Component',
            'description' => 'Test component description',
        ];
    }

    public function onAjaxHandler()
    {
        return ['result' => 'handler-result'];
    }

    public function onAjaxHandlerWithStringResponse()
    {
        return 'handler-result';
    }

    public function onAjaxHandlerWithObjectResponse()
    {
        return response()->json(['json' => 'handler-result']);
    }

    public function onAjaxHandlerWithRedirect()
    {
        return redirect()->to('http://localhost');
    }

    public function onAjaxHandlerWithFlash()
    {
        flash()->success('Flash message');
    }

    public function onAjaxHandlerWithValidationError()
    {
        Validator::make([], [
            'name' => 'required',
        ])->validate();
    }
}
