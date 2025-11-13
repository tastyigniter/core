<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Pagic;

use Igniter\Flame\Pagic\Exception\MissingFileNameException;
use Igniter\Flame\Pagic\Finder;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Template\Page;
use Igniter\Main\Template\Partial;
use Igniter\Tests\Flame\Pagic\Fixtures\TestPage;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;

it('creates and deletes a new model instance with attributes', function() {
    Partial::clearBootedModels();
    $model = Partial::on('tests-theme');
    $model->fileName = 'new-test-partial';
    @unlink($model->getFilePath());

    $model = $model->create(['fileName' => 'new-test-partial', 'content' => 'test content']);

    expect($model->getContent())->toBe('test content');

    $model->delete();
});

it('returns false if creating event returns false', function() {
    $model = Partial::on('tests-theme');
    $model->fileName = 'new-test-partial';
    Event::listen('pagic.creating: '.Partial::class, fn(): false => false);

    $model = $model->create(['fileName' => 'new-test-partial', 'content' => 'test content']);

    expect($model->exits)->not->toBeTrue();
});

it('returns false if deleting event returns false', function() {
    $model = Partial::on('tests-theme');
    $model->fileName = 'new-test-partial';

    expect($model->delete())->toBeFalse();

    $model = Partial::load('tests-theme', 'test-partial');
    $oldContent = file_get_contents($model->getFilePath());
    Event::listen('pagic.deleting: '.Partial::class, fn(): false => false);

    expect($model->delete())->toBeFalse();
    file_put_contents($model->getFilePath(), $oldContent);
});

it('updates an existing model instance', function() {
    $model = Partial::load('tests-theme', 'test-partial');
    $model->fillable(['fileName', 'content']);

    $oldContent = file_get_contents($model->getFilePath());

    $model->update(['content' => 'updated content']);
    file_put_contents($model->getFilePath(), $oldContent);

    expect($model->getContent())->toBe('updated content')
        ->and($model->getFilePath('test-partial'))->toEndWith('test-partial.blade.php')
        ->and($model->getDefaultExtension())->toBe('blade.php')
        ->and($model->getCode())->toBeNull();

    $model = Partial::on('tests-theme');
    $model->fileName = 'new-test-partial';
    $model->layout = 'default';
    $model['custom'] = [1, 2, 3];

    expect($model->update(['content' => 'test content']))->toBeBool();
    unset($model->layout, $model['custom']);
    @unlink($model->getFilePath());
});

it('returns false if saveInternal event returns false', function() {
    $model = new Page;
    $model->fill(['content' => 'test content']);
    $model->bindEvent('model.saveInternal', fn(): false => false);

    expect($model->save())->toBeFalse();
});

it('returns false if saving event returns false', function() {
    $model = new Partial;
    $model->fill(['content' => 'test content']);
    Event::listen('pagic.saving: '.Partial::class, fn(): false => false);

    expect($model->save())->toBeFalse();
});

it('returns false if updating event returns false', function() {
    $model = Partial::load('tests-theme', 'test-partial');
    $oldContent = file_get_contents($model->getFilePath());
    Event::listen('pagic.updating: '.Partial::class, fn(): false => false);

    $result = $model->update(['content' => 'updated content']);
    file_put_contents($model->getFilePath(), $oldContent);

    expect($result)->toBeFalse();
});

it('throws exception when deleting a model without file name', function() {
    $model = new Page;
    expect(fn() => $model->delete())->toThrow(InvalidArgumentException::class, 'No file name (fileName) defined on model.')
        ->and(fn() => $model->invalidMethod())->toThrow('Call to undefined method '.Finder::class.'::invalidMethod()');
});

it('queries model correctly', function() {
    expect(Page::find('fileName'))->toBeNull()
        ->and(Page::query())->toBeInstanceOf(Finder::class)
        ->and(Page::all())->toBeCollection();
});

it('adds mutated attributes to array', function() {
    $model = new class extends Page
    {
        public array $attributes = ['name' => 'test'];

        public function getNameAttribute($value): string
        {
            return strtoupper((string)$value);
        }
    };
    expect($model->attributesToArray())->toBe(['name' => 'TEST']);
});

it('returns model id based on file name', function() {
    $model = new Page;
    $model->fileName = 'account/login.blade.php';

    expect($model->getId())->toBe('account-login')
        ->and($model->getKey())->toBe('account.login')
        ->and($model->getKeyName())->toBeNull();
});

it('converts model to array & JSON', function() {
    $attributes = ['name' => 'test', 'content' => null];
    $expected = [
        'settings' => [
            'name' => 'test',
        ],
        'content' => null,
        'extra' => 'extra',
    ];
    $model = new class($attributes) extends Page
    {
        protected array $appends = ['extra'];

        protected array $hidden = ['custom'];

        protected array $visible = ['settings', 'content', 'extra'];

        protected array $original = ['content' => 'test content'];

        public function getExtraAttribute(): string
        {
            return 'extra';
        }
    };
    $model->bindEvent('model.beforeGetAttribute', fn($key): ?string => $key === 'custom' ? 'custom' : null);
    $model->bindEvent('model.getAttribute', fn($key, $attr): ?string => $key === 'extra' ? 'extra' : null);
    $model->syncOriginalAttribute('content');
    $model->syncChanges();
    $model->isClean($attributes);
    $model->wasChanged([]);
    Page::addGlobalScope('test');

    expect($model->toArray())->toBe($expected)
        ->and($model->toJson())->toBe(json_encode($expected))
        ->and((string)$model)->toBe(json_encode($expected))
        ->and($model->getAttribute(''))->toBeNull()
        ->and($model->getAttribute('custom'))->toBe('custom')
        ->and($model->getAttribute('extra'))->toBe('extra')
        ->and($model->only(['name']))->toBeArray()
        ->and($model->isLoadedFromCache())->toBeBool();
});

it('adds observables', function() {
    Page::flushEventListeners();
    $model = new TestPage;
    $model->observe(new class
    {
        public function saved(): string
        {
            return 'test';
        }
    });
    Event::dispatch('pagic.saved: '.TestPage::class, [$model]);
    $model->setObservableEvents(['testing', 'tested']);
    $model->addObservableEvents(['test']);
    $model->removeObservableEvents(['test']);

    $dispatcher = $model->getEventDispatcher();
    $model->unsetEventDispatcher();
    expect(fn() => $model->save())->toThrow(MissingFileNameException::class);
    $model->setEventDispatcher($dispatcher);
});

it('manages visible and hidden attributes', function() {
    $model = new TestPage;
    TestPage::unsetCacheManager();
    $model->setVisible(['settings', 'content']);
    $model->addVisible(['extra']);
    $model->makeVisible(['custom']);
    $model->setHidden(['settings', 'content']);
    $model->addHidden('extra');
    $model->makeHidden('custom');

    expect($model->getVisible())->toBe(['settings', 'content', 'extra'])
        ->and($model->getHidden())->toBe(['settings', 'content', 'extra', 'custom']);
});

it('lists all pages in theme', function() {
    $theme = resolve(ThemeManager::class)->findTheme('tests-theme');

    expect(Page::listInTheme($theme))->toBeCollection()
        ->and(Page::getDropdownOptions($theme))->toBeArray()
        ->and(Page::unsetSourceResolver())->toBeNull();

    Page::unsetSourceResolver();
});
