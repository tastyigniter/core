<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Actions;

use Igniter\Admin\Models\Status;
use Igniter\System\Actions\ModelAction;
use LogicException;

it('initializes with valid model', function() {
    $model = new class(new Status) extends ModelAction
    {
        public $requiredProperty = 'value';
    };

    expect($model->requiredProperty)->toBe('value');
});

it('throws exception if required property is missing', function() {
    expect(fn() => new class(new Status) extends ModelAction
    {
        protected array $requiredProperties = ['requiredProperty'];
    })->toThrow(LogicException::class, sprintf(
        'Class %s must define property %s used by %s',
        Status::class, 'requiredProperty', ModelAction::class,
    ));
});

it('initializes with null model', function() {
    expect(new ModelAction())->not->toBeNull();
});
