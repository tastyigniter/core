<?php

namespace Igniter\Tests\Flame\Pagic;

use Igniter\Flame\Pagic\Exception\InvalidExtensionException;
use Igniter\Flame\Pagic\Exception\InvalidFileNameException;
use Igniter\Flame\Pagic\Exception\MissingFileNameException;
use Igniter\Flame\Pagic\Finder;
use Igniter\Flame\Pagic\Model;
use Igniter\Flame\Pagic\Processors\Processor;
use Igniter\Flame\Pagic\Source\MemorySource;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Template\Page;

beforeEach(function() {
    $theme = resolve(ThemeManager::class)->findTheme('tests-theme');
    $this->source = $theme->makeFileSource();
    $processor = new Processor;
    $this->finder = new Finder($this->source, $processor);
});

it('sets offset when skip is called', function() {
    $this->finder->skip(10);
    expect($this->finder->offset)->toBe(10);
});

it('sets limit when take is called', function() {
    $this->finder->take(5);
    expect($this->finder->limit)->toBe(5);
});

it('sets and gets the directory name correctly', function() {
    $this->finder->in('templates');
    expect($this->finder->in)->toBe('templates');
});

it('sets and gets the offset correctly', function() {
    $this->finder->offset(10);
    expect($this->finder->offset)->toBe(10);
});

it('sets and gets the limit correctly', function() {
    $this->finder->limit(5);
    expect($this->finder->limit)->toBe(5);
});

it('throws exception when validating file name fails', function($fileName, $exception) {
    $model = new class extends Model {};
    $model->fileName = $fileName;
    $this->finder->setModel($model);
    expect(fn() => $this->finder->insert(['content' => 'this is the content']))->toThrow($exception);
})->with([
    'empty name' => ['', MissingFileNameException::class],
    'invalid extension' => ['template.txt', InvalidExtensionException::class],
    'invalid path ../' => ['../invalid/template.php', InvalidFileNameException::class],
    'invalid path ./' => ['./invalid/template.php', InvalidFileNameException::class],
    'exceed max nesting' => ['dir1/dir2/template.php', InvalidFileNameException::class],
    'invalid character' => ['valid/templ@te.php', InvalidFileNameException::class],
]);

it('returns null when file is not found', function() {
    $model = new class extends Model {};
    $this->finder->setModel($model);
    $this->finder->in('templates');
    expect($this->finder->find('nonexistent-file'))->toBeNull();
});

it('returns model when file is found', function() {
    $model = Page::load('tests-theme', 'nested-page');
    $this->finder->setModel($model);
    $this->finder->in('_pages');
    expect($this->finder->find('nested-page'))->toEqual($model);
});

it('returns cached results when cache is available', function() {
    $model = Page::load('tests-theme', 'nested-page');
    $modelArray = $model->toArray();
    $modelArray['mTime'] = $modelArray['mTime'] + 1000;
    cache()->put('cache_key', [$modelArray], 10);
    $this->finder->setModel($model);
    $this->finder->in('_pages');
    $this->finder->whereFileName('nested-page');
    $this->finder->cacheTags(['tag1', 'tag2']);
    $this->finder->cacheTags([]);
    $this->finder->cacheDriver(config('cache.default'));
    expect($this->finder->remember(10, 'cache_key')->get()->count())->toBeGreaterThan(0);

    MemorySource::$cache = [];
    cache()->put('cache_key', [$modelArray], 10);
    $this->finder->whereFileName('nested-page');
    expect($this->finder->rememberForever('cache_key')->get()->count())->toBeGreaterThan(0);

    MemorySource::$cache = [];
    cache()->put('cache_key', [$modelArray], 10);
    $this->finder->select = [];
    expect($this->finder->rememberForever('cache_key')->get()->count())->toBeGreaterThan(0);

    MemorySource::$cache = [];
    expect($this->finder->rememberForever()->get()->count())->toBeGreaterThan(0)
        ->and($this->finder->lists('fileName')->count())->toBeGreaterThan(0);

    Finder::clearInternalCache();
});

it('inserts a new record successfully', function() {
    $model = new Page;
    $model->fileName = 'new-template';
    $this->finder->setModel($model);
    expect($this->finder->insert([]))->toBe(1)
        ->and($this->finder->insert(['content' => 'this is the content']))->toBeInt()
        ->and($this->finder->lastModified())->toBeInt();
    unlink($this->source->getBasePath().'/_pages/new-template.blade.php');
});

it('updates and deletes a record successfully', function() {
    $oldContent = file_get_contents($this->source->getBasePath().'/_pages/nested-page.blade.php');
    $model = Page::load('tests-theme', 'nested-page');
    $model->fileName = 'new-nested-page';
    $this->finder->setModel($model);
    $this->finder->in('_pages');
    expect($this->finder->update(['content' => 'this is the updated content']))->toBeInt();
    file_put_contents($this->source->getBasePath().'/_pages/nested-page.blade.php', $oldContent);

    $model = Page::load('tests-theme', 'new-nested-page');
    $this->finder->setModel($model);
    expect($this->finder->delete())->toBeTrue()
        ->and($this->finder->getSource())->toEqual($this->source);
});
