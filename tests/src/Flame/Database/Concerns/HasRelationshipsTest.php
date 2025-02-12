<?php

namespace Igniter\Tests\Flame\Database\Concerns;

use Igniter\Flame\Database\Concerns\HasRelationships;
use Igniter\Flame\Database\Model;
use Igniter\Tests\Fixtures\Models\TestModel;
use Illuminate\Database\Eloquent\Relations\Relation;
use InvalidArgumentException;

it('checks if relation exists', function() {
    $model = new class extends Model
    {
        use HasRelationships;
    };

    $model->relation['hasOne']['relationName'] = ['RelatedModel'];
    expect($model->hasRelation('relationName'))->toBeTrue();
});

it('returns null if relation does not exist', function() {
    $model = new class extends Model
    {
        use HasRelationships;
    };

    expect($model->getRelationDefinition('nonexistentRelation'))->toBeNull();
});

it('returns relation definition', function() {
    $model = new class extends Model
    {
        use HasRelationships;
    };

    $model->relation['hasOne']['relationName'] = ['RelatedModel'];
    expect($model->getRelationDefinition('relationName'))->toBe(['RelatedModel']);
});

it('returns all relation definitions', function() {
    $model = new class extends Model
    {
        use HasRelationships;
    };
    $model->relation['hasOne']['relationName'] = ['RelatedModel'];
    $model->relation['hasMany']['anotherRelation'] = ['AnotherModel'];

    $definitions = $model->getRelationDefinitions();

    expect($definitions['hasOne']['relationName'])->toBe(['RelatedModel'])
        ->and($definitions['hasMany']['anotherRelation'])->toBe(['AnotherModel']);
});

it('returns relation type', function() {
    $model = new class extends Model
    {
        use HasRelationships;
    };
    $model->relation['hasOne']['relationName'] = ['RelatedModel'];
    expect($model->getRelationType('relationName'))->toBe('hasOne');
});

it('returns null for non-existent relation type', function() {
    $model = new class extends Model
    {
        use HasRelationships;
    };
    expect($model->getRelationType('nonexistentRelation'))->toBeNull();
});

it('returns relation value if loaded', function() {
    $model = new class extends Model
    {
        use HasRelationships;

        protected $relations = ['relationName' => 'relationValue'];
    };
    expect($model->getRelationValue('relationName'))->toBe('relationValue');
});

it('creates relation instance', function() {
    $model = new class extends Model
    {
        use HasRelationships;
    };
    $model->relation['morphTo']['relationName'] = ['RelatedModel'];
    expect($model->makeRelation('relationName'))->toBeNull()
        ->and(fn() => $model->handleRelation('relationName'))->toThrow(InvalidArgumentException::class);
});

it('returns true if relation definition does not have push key', function() {
    $model = new class extends Model
    {
        use HasRelationships;
    };
    $model->relation['hasOne']['relationName'] = ['RelatedModel'];
    expect($model->isRelationPushable('relationName'))->toBeTrue();
});

it('returns true if relation is pushable', function() {
    $model = new class extends Model
    {
        use HasRelationships;
    };
    $model->relation['hasOne']['relationName'] = ['RelatedModel', 'push' => true];
    expect($model->isRelationPushable('relationName'))->toBeTrue();
});

it('throws exception for invalid relation arguments', function() {
    $model = new class extends Model
    {
        use HasRelationships;
    };
    $model->relation['hasOne']['relationName'] = ['RelatedModel'];
    $model->handleRelation('invalidRelation');
})->throws(InvalidArgumentException::class);

it('handles hasOne relation', function() {
    $model = new class extends Model
    {
        use HasRelationships;
    };
    $model->relation['hasOne']['relationName'] = [TestModel::class];
    $relation = $model->handleRelation('relationName');

    expect($relation->getRelated())->toBeInstanceOf(TestModel::class);
});

it('handles hasOneThrough & hasManyThrough relation', function() {
    $model = new class extends Model
    {
        use HasRelationships;

        protected $primaryKey = 'id';
    };
    $model->relation['hasOneThrough']['oneName'] = [TestModel::class, 'through' => TestModel::class];
    $model->relation['hasManyThrough']['relationName'] = [TestModel::class, 'through' => TestModel::class];
    $relation = $model->handleRelation('relationName');
    $relation2 = $model->handleRelation('oneName');

    expect($relation->getParent())->toBeInstanceOf(TestModel::class)
        ->and($relation2->getParent())->toBeInstanceOf(TestModel::class)
        ->and($relation->getRelated())->toBeInstanceOf(TestModel::class)
        ->and($relation->getFirstKeyName())->toEndWith('_id');
});

it('handles morphedByMany relation', function() {
    Relation::morphMap(['test_model' => TestModel::class]);
    $model = new class extends Model
    {
        use HasRelationships;
    };
    $model->relation['morphedByMany']['relationName'] = [
        TestModel::class,
        'name' => 'table',
    ];
    $relation = $model->handleRelation('relationName');

    expect($relation->getMorphType())->toBe('table_type')
        ->and($relation->getMorphClass())->toBe('test_model');
});

it('handles morphOne relation', function() {
    Relation::morphMap(['test_model' => TestModel::class]);
    $model = new class extends Model
    {
        use HasRelationships;

        public function getMorphClass()
        {
            return 'test_model_anony';
        }
    };
    $model->relation['morphOne']['relationName'] = [TestModel::class, 'name' => 'table'];
    $relation = $model->handleRelation('relationName');

    expect($relation->getMorphType())->toBe('table_type')
        ->and($relation->getMorphClass())->toBe('test_model_anony');
});

it('handles invalid relation type', function() {
    $model = new class extends Model
    {
        use HasRelationships;
    };
    $model->relation['inValidType']['invalidRelationName'] = [TestModel::class];
    $model->handleRelation('invalidRelationName');
})->throws(InvalidArgumentException::class);

it('validates relation arguments', closure: function() {
    $model = new class extends Model
    {
        use HasRelationships;
    };

    $model->relation['hasManyThrough']['relationName'] = ['RelatedModel', 'foreignKey' => 'foreign_key'];
    $model->handleRelation('relationName');
})->throws(InvalidArgumentException::class);
