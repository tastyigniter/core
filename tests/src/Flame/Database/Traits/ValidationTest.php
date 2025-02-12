<?php

namespace Igniter\Tests\Flame\Database\Traits;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\Validation;
use Igniter\Tests\Flame\Database\Fixtures\TestModelForValidation;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use LogicException;

beforeEach(function() {
    TestModelForValidation::flushEventListeners();
});

it('throws exception if rules property is not defined', function() {
    $this->expectException(LogicException::class);
    $this->expectExceptionMessage('You must define a $rules property in ');

    new class extends Model
    {
        use Validation;
    };
});

it('validates model on saving', function() {
    Event::fake([
        'eloquent.validated: '.TestModelForValidation::class,
    ]);
    $model = new TestModelForValidation;
    $model->country_name = 'Test Country';
    $model->iso_code_2 = 'qq';
    $model->format = '!1,00.0';
    $model->exists = true;

    expect($model->save())->toBeTrue();

    Event::assertDispatched('eloquent.validated: '.TestModelForValidation::class, function($model, $status) {
        return $status[1] === 'passed';
    });
});

it('validates model on restoring', function() {
    Event::fake([
        'eloquent.validated: '.TestModelForValidation::class,
    ]);
    $model = new TestModelForValidation;
    $model->country_name = 'Test Country';
    $model->iso_code_2 = 'qq';
    $model->format = '!1,00.0';
    $model->exists = true;

    $model->fireEvent('model.restoring');

    Event::assertDispatched('eloquent.validated: '.TestModelForValidation::class, function($model, $status) {
        return $status[1] === 'passed';
    });
});

it('skips validation if validating is disabled', function() {
    Event::fake([
        'eloquent.validated: '.TestModelForValidation::class,
    ]);
    $model = new TestModelForValidation;
    $model->setValidating(false);
    $model->country_name = 'Test Country';

    expect($model->save())->toBeTrue();

    Event::assertDispatched('eloquent.validated: '.TestModelForValidation::class, function($model, $status) {
        return $status[1] === 'skipped';
    });
});

it('throws validation exception on failed validation', function() {
    $model = new TestModelForValidation;
    $model->country_name = null;

    expect(fn() => $model->save())->toThrow(ValidationException::class, 'The country name field is required.')
        ->and($model->getErrors()->all())->toContain('The country name field is required.');
});

it('skips validation using model.beforeValidate event', function() {
    Event::fake([
        'eloquent.validated: '.TestModelForValidation::class,
    ]);
    $model = new TestModelForValidation;
    $model->country_name = 'Test Country';
    $model->bindEvent('model.beforeValidate', function() {
        return false;
    });

    $model->save();

    Event::assertNotDispatched('eloquent.validated: '.TestModelForValidation::class);
});

it('skips validation using eloquent.validating event', function() {
    Event::listen('eloquent.validating: '.TestModelForValidation::class, function() {
        return false;
    });
    $model = new TestModelForValidation;
    $model->country_name = 'Test Country';
    $model->setInjectUniqueIdentifier(true);

    expect($model->save())->toBeTrue()
        ->and($model->getInjectUniqueIdentifier())->toBeTrue();
});

it('skips validation using beforeValidate method', function() {
    $model = new class extends TestModelForValidation
    {
        public function beforeValidate()
        {
            return true;
        }
    };
    $model->country_name = 'Test Country';

    expect($model->save())->toBeTrue();
});
