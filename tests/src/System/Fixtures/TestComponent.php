<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Fixtures;

use Illuminate\Support\Facades\Validator;

class TestComponent extends \Igniter\System\Classes\BaseComponent
{
    public static function componentMeta(): array
    {
        return [
            'code' => 'testComponent',
            'name' => 'Test Component',
            'description' => 'Test component description',
        ];
    }

    public function onAjaxHandler(): array
    {
        return ['result' => 'handler-result'];
    }

    public function onAjaxHandlerWithStringResponse(): string
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

    public function onAjaxHandlerWithFlash(): void
    {
        flash()->success('Flash message');
    }

    public function onAjaxHandlerWithValidationError(): void
    {
        Validator::make([], [
            'name' => 'required',
        ])->validate();
    }
}
