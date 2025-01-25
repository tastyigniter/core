<?php

namespace Igniter\Tests\Admin\Traits;

use Igniter\Admin\Traits\ValidatesForm;
use Igniter\Admin\Widgets\Form;
use Igniter\Tests\Fixtures\Requests\TestRequest;
use Illuminate\Validation\ValidationException;

beforeEach(function() {
    $this->traitObject = new class
    {
        use ValidatesForm;
    };
});

it('validates request successfully with valid data', function() {
    $request = ['name' => 'John Doe'];
    $rules = ['name' => 'required|string'];

    $this->traitObject->validateAfter(function($request) {
        return $request;
    });
    $result = $this->traitObject->validate($request, $rules);

    expect($result)->toBe($request);
});

it('throws validation exception with invalid data', function() {
    $request = ['name' => ''];
    $rules = ['name' => 'required|string'];

    expect(fn() => $this->traitObject->validate($request, $rules))->toThrow(ValidationException::class);
});

it('returns false when validation fails', function() {
    $request = ['name' => ''];
    $rules = ['name' => 'required|string'];

    $result = $this->traitObject->validatePasses($request, $rules);

    expect($result)->toBeFalse();
});

it('returns validated data when validation passes', function() {
    $request = ['name' => 'John Doe'];
    $rules = ['name' => 'required|string'];

    $result = $this->traitObject->validatePasses($request, $rules);

    expect($result)->toBe($request);
});

it('parses rules correctly', function() {
    $rules = [
        ['name', 'Name', 'required|string'],
    ];
    $expected = ['name' => 'required|string'];

    $result = $this->traitObject->parseRules($rules);

    expect($result)->toBe($expected);
});

it('parses rules returns empty array when no rules provided', function() {
    $rules = [];
    $expected = [];

    $result = $this->traitObject->parseRules($rules);

    expect($result)->toBe($expected);
});

it('parses attributes correctly', function() {
    $rules = [
        ['name', 'Name', 'required|string'],
    ];
    $expected = ['name' => 'Name'];

    $result = $this->traitObject->parseAttributes($rules);

    expect($result)->toBe($expected);
});

it('parses attributes returns empty array when no attributes provided', function() {
    $rules = [];
    $expected = [];

    $result = $this->traitObject->parseAttributes($rules);

    expect($result)->toBe($expected);
});

it('flashes validation errors to session', function() {
    $trait = new class
    {
        use ValidatesForm;

        public function testFlashValidationErrors(array $errors)
        {
            $this->flashValidationErrors($errors);
        }
    };

    $errors = ['name' => ['The name field is required.']];

//    session()->flash('errors', $errors);

    $trait->testFlashValidationErrors($errors);

    expect(session('errors'))->toBe($errors);
});

it('validates form widget with rules in config', function() {
    $trait = new class
    {
        use ValidatesForm;

        public $config = [];

        public function testValidateFormWidget(Form $form, mixed $saveData): mixed
        {
            return $this->validateFormWidget($form, $saveData);
        }
    };

    $form = new class extends Form
    {
        public ?array $config = ['rules' => ['name' => 'required|string']];

        public function __construct() {}
    };

    $saveData = ['name' => 'John Doe'];

    $result = $trait->testValidateFormWidget($form, $saveData);

    expect($result)->toBe($saveData);
});

it('validates form widget with form request class', function() {
    $trait = new class
    {
        use ValidatesForm;

        public $config = ['request' => TestRequest::class];

        public function testValidateFormWidget(Form $form, mixed $saveData): mixed
        {
            return $this->validateFormWidget($form, $saveData);
        }
    };

    $form = new class extends Form
    {
        public ?array $config = [];

        public function __construct() {}
    };

    $saveData = ['name' => 'John Doe'];

    $result = $trait->testValidateFormWidget($form, $saveData);

    expect($result)->toBe($saveData);
});

it('validates form request class', function() {
    $trait = new class
    {
        use ValidatesForm;

        public function testValidateFormRequest(?string $requestClass, callable $callback): array
        {
            return $this->validateFormRequest($requestClass, $callback);
        }
    };
    $saveData = ['name' => 'John Doe'];
    request()->merge($saveData);

    $result = $trait->testValidateFormRequest(TestRequest::class, fn($request) => true);

    expect($result)->toBe($saveData);
});
