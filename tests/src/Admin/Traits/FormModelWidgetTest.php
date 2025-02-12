<?php

namespace Igniter\Tests\Admin\Traits;

use Igniter\Admin\Classes\FormField;
use Igniter\Admin\Models\Status;
use Igniter\Admin\Models\StatusHistory;
use Igniter\Admin\Traits\FormModelWidget;
use Igniter\Flame\Database\Relations\HasMany;
use Igniter\Flame\Exception\FlashException;
use Igniter\Tests\Fixtures\Models\IlluminateModel;

it('creates form model correctly', function() {
    $widget = new class
    {
        use FormModelWidget;

        public $modelClass = Status::class;
    };

    $model = $widget->createFormModel();

    expect($model)->toBeInstanceOf(Status::class);
});

it('throws exception when model class is missing', function() {
    $widget = new class
    {
        public $modelClass;

        use FormModelWidget;
    };

    expect(fn() => $widget->createFormModel())->toThrow(FlashException::class);
});

it('finds form model by record ID', function() {
    $widget = new class
    {
        use FormModelWidget;

        public $modelClass = Status::class;
    };

    $model = Status::factory()->create();

    $result = $widget->findFormModel($model->getKey());

    expect($result->getKey())->toBe($model->getKey());
});

it('throws exception when record ID is missing', function() {
    $widget = new class
    {
        use FormModelWidget;

        public $modelClass = Status::class;
    };

    expect(fn() => $widget->findFormModel(''))->toThrow(FlashException::class);
});

it('throws exception when record not found', function() {
    $widget = new class
    {
        use FormModelWidget;

        public $modelClass = Status::class;
    };

    expect(fn() => $widget->findFormModel('123'))->toThrow(FlashException::class);
});

it('resolves model attribute correctly', function() {
    $widget = new class
    {
        use FormModelWidget;

        public $formField;

        public $model;
    };
    $widget->formField = new FormField('text', 'Text field');
    $widget->model = Status::factory()->create();

    [$model, $attribute] = $widget->resolveModelAttribute('status_history');

    expect($model)->toBe($widget->model)
        ->and($attribute)->toBe('status_history');
});

it('returns null when resolving model attribute fails', function() {
    $widget = new class
    {
        use FormModelWidget;

        public $formField;

        public $model;
    };
    $widget->formField = new FormField('text', 'Text field');
    $widget->model = Status::factory()->create();

    $result = $widget->resolveModelAttribute('status[invalid_attribute]');

    expect($result)->toBe([null, 'invalid_attribute']);
});

it('returns relation model correctly', function() {
    $widget = new class
    {
        use FormModelWidget;

        public $model;

        public $formField;

        public $valueFrom = 'status_history';

        public function testGetRelationModel()
        {
            return $this->getRelationModel();
        }
    };

    $widget->formField = new FormField('text', 'Text field');
    $widget->model = Status::factory()->create();

    expect($widget->testGetRelationModel())->toBeInstanceOf(StatusHistory::class);
});

it('throws exception when getting relation model fails', function() {
    $widget = new class
    {
        use FormModelWidget;

        public $model;

        public $formField;

        public $valueFrom = 'invalid_relation';

        public function testGetRelationModel()
        {
            $this->formField = new FormField('text', 'Text field');
            $this->model = Status::factory()->create();

            return $this->getRelationModel();
        }
    };

    expect(fn() => $widget->testGetRelationModel())->toThrow(FlashException::class);
});

it('returns relation model instance correctly', function() {
    $widget = new class
    {
        use FormModelWidget;

        public $formField;

        public $model;

        public $valueFrom = 'status_history';

        public function testGetRelationObject()
        {
            $this->formField = new FormField('text', 'Text field');
            $this->model = Status::factory()->create();

            return $this->getRelationObject();
        }
    };

    expect($widget->testGetRelationObject())->toBeInstanceOf(HasMany::class);
});

it('throws exception when getting relation model instance fails', function() {
    $widget = new class
    {
        use FormModelWidget;

        public $formField;

        public $model;

        public $valueFrom = 'invalid_relation';

        public function testGetRelationObject()
        {
            $this->formField = new FormField('text', 'Text field');
            $this->model = Status::factory()->create();

            return $this->getRelationObject();
        }
    };

    expect(fn() => $widget->testGetRelationObject())->toThrow(FlashException::class);
});

it('returns relation type correctly', function() {
    $widget = new class
    {
        use FormModelWidget;

        public $model;

        public $formField;

        public $valueFrom = 'status_history';

        public function testGetRelationType()
        {
            return $this->getRelationType();
        }
    };

    $widget->formField = new FormField('text', 'Text field');
    $widget->model = Status::factory()->create();

    expect($widget->testGetRelationType())->toBe('hasMany');
});

it('makes model relation correctly', function() {
    $widget = new class
    {
        use FormModelWidget;

        public $model;

        public $valueFrom = 'status_history';

        public function testMakeModelRelation()
        {
            return $this->makeModelRelation($this->model, $this->valueFrom);
        }
    };
    $widget->model = new IlluminateModel;

    expect($widget->testMakeModelRelation())->toBeInstanceOf(StatusHistory::class);
});

it('sets model attributes with nested data', function() {
    $model = new IlluminateModel;
    $widget = new class
    {
        use FormModelWidget;

        public function testSetModelAttributes($model, $saveData)
        {
            return $this->setModelAttributes($model, $saveData);
        }
    };

    $saveData = ['status_history' => [['comment' => 'Some comment']]];

    expect(fn() => $widget->testSetModelAttributes($model, $saveData))->toThrow(\LogicException::class);
});

it('does not set attributes starting with underscore', function() {
    $model = new Status;
    $widget = new class
    {
        use FormModelWidget;

        public function testSetModelAttributes($model, $saveData)
        {
            return $this->setModelAttributes($model, $saveData);
        }
    };

    $saveData = ['_token' => 'some_token', 'status_name' => 'Pending'];
    $widget->testSetModelAttributes($model, $saveData);

    expect($model->status_name)->toBe('Pending')
        ->and(isset($model->_token))->toBeFalse();
});

it('skips setting attributes with NO_SAVE_DATA', function() {
    $model = new Status;
    $widget = new class
    {
        use FormModelWidget;

        public function testSetModelAttributes($model, $saveData)
        {
            return $this->setModelAttributes($model, $saveData);
        }
    };

    $saveData = ['status_name' => FormField::NO_SAVE_DATA, 'status_for' => 'example'];
    $widget->testSetModelAttributes($model, $saveData);

    expect($model->status_for)->toBe('example')
        ->and($model->status_name)->toBeNull();
});
