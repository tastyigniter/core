<?php

namespace Igniter\Tests\Flame\Database;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Pivot;
use Igniter\System\Models\Currency;
use Igniter\System\Models\Language;
use Igniter\System\Models\MailLayout;
use Igniter\System\Models\MailTemplate;
use Igniter\User\Models\User;
use Igniter\User\Models\UserGroup;
use Illuminate\Support\Facades\Event;

it('calls afterBoot method if it exists', function() {
    $model = new class extends Model
    {
        public static bool $afterBootCalled = false;

        public static function afterBoot()
        {
            static::$afterBootCalled = true;
        }
    };

    expect($model::$afterBootCalled)->toBeTrue();
});

it('returns instance if fetching event returns false', function() {
    $events = [];
    Event::listen('eloquent.fetching: '.Currency::class, function() use (&$events) {
        $events[] = 'eloquent.fetching';
        return false;
    });
    Event::listen('eloquent.fetched: '.Currency::class, function() use (&$events) {
        $events[] = 'eloquent.fetched';
        return false;
    });

    Currency::query()->find(1);

    expect($events)->toContain('eloquent.fetching')->not->toContain('eloquent.fetched');
});

it('creates a new pivot model instance using the provided class', function() {
    $model = mock(Model::class)->makePartial();
    $parent = mock(Model::class)->makePartial();
    $attributes = ['attribute' => 'value'];
    $table = 'pivot_table';
    $exists = true;
    $using = mock(Pivot::class);

    $using->shouldReceive('fromRawAttributes')->with($parent, $attributes, $table, $exists)->andReturnSelf();

    $result = $model->newPivot($parent, $attributes, $table, $exists, get_class($using));
    expect($result)->toBe($using);
});

it('creates a new pivot model instance if pivotModel is defined', function() {
    $model = mock(Model::class)->makePartial();
    $parent = mock(Model::class)->makePartial();
    $attributes = ['attribute' => 'value'];
    $table = 'pivot_table';
    $exists = true;

    $model->shouldReceive('getRelationDefinition')->with('relationName')->andReturn(['pivotModel' => Pivot::class]);

    $result = $model->newRelationPivot('relationName', $parent, $attributes, $table, $exists);
    expect($result)->toBeInstanceOf(Pivot::class);
});

it('returns false if saveInternal event returns false', function() {
    $model = new Currency();
    $model->fill(['currency_name' => 'United States Dollar']);
    $model->bindEvent('model.saveInternal', function() {
        return false;
    });

    expect($model->save())->toBeFalse();
});

it('returns false if saving event returns false', function() {
    $model = new Currency();
    $model->fill(['currency_name' => 'United States Dollar']);
    Event::listen('eloquent.saving: '.Currency::class, function() {
        return false;
    });

    expect($model->save())->toBeFalse();
});

it('fires updated and saved events if parent save returns null', function() {
    $model = mock(Model::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $model->shouldReceive('performInsert')->andReturn(null);

    expect($model->save())->toBeNull();
});

it('returns false if save fails and always option is false', function() {
    $model = new Currency();
    $model->fill(['currency_name' => 'United States Dollar']);
    $model->bindEvent('model.saveInternal', function() {
        return false;
    });

    expect($model->push())->toBeFalse();
});

it('skip pushing relations when push is disabled', function() {
    $model = new class extends Currency
    {
        public $relation = [
            'belongsTo' => [
                'country' => [\Igniter\System\Models\Country::class, 'push' => false],
            ],
        ];
    };
    $model->save();
    $model->country;

    expect($model->push())->toBeTrue();
});

it('returns false if a relation push fails', function() {
    $model = MailLayout::factory()->create();
    $model->templates;
    $model->language;

    $model->language->fill(['name' => 'New Language']);
    $model->language->bindEvent('model.saveInternal', function() {
        return false;
    });

    expect($model->push())->toBeFalse();
});

it('returns true if all relations push successfully', function() {
    $model = MailLayout::factory()->create();
    $model->templates;
    $model->language;

    expect($model->alwaysPush())->toBeTrue();
});

it('deletes the model and its relations', function() {
    $model = new class extends MailLayout
    {
        public $relation = [
            'hasMany' => [
                'templates' => [\Igniter\System\Models\MailTemplate::class, 'foreignKey' => 'layout_id', 'delete' => true],
            ],
            'belongsTo' => [
                'language' => [\Igniter\System\Models\Language::class, 'delete' => true],
            ],
        ];
    };
    $model->language()->associate($language = Language::factory()->create());
    $model->save();
    $model->templates()->save($mailTemplate = MailTemplate::create(['code' => '_mail.test_template']));

    $model->delete();

    expect(MailLayout::find($model->getKey()))->toBeNull()
        ->and(Language::find($language->getKey()))->toBeNull()
        ->and(MailTemplate::find($mailTemplate->getKey()))->toBeNull();
});

it('deletes the model and its belongsToMany relations', function() {
    $model = User::factory()->create();
    $model->relation['belongsToMany']['groups'] = [
        \Igniter\User\Models\UserGroup::class, 'table' => 'admin_users_groups', 'delete' => true,
    ];
    $model->groups()->attach($group = UserGroup::factory()->create());

    $model->delete();

    expect(User::find($model->getKey()))->toBeNull()
        ->and(UserGroup::find($group->getKey()))->not->toBeNull();
});
