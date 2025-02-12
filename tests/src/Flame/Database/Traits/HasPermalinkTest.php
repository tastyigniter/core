<?php

namespace Igniter\Tests\Flame\Database\Traits;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\HasPermalink;
use Igniter\System\Models\Page;
use LogicException;

it('throws exception if permalinkable property is not defined', function() {
    $this->expectException(LogicException::class);
    $this->expectExceptionMessage('You must define a $permalinkable property in');

    new class extends Model
    {
        use HasPermalink;
    };
});

it('generates permalink on save', function() {
    $page = Page::factory()->make(['permalink_slug' => null]);
    $page->save();

    expect($page->permalink_slug)->not->toBeNull();
});

it('returns slug key name from property', function() {
    $page = new class extends Page
    {
        protected $slugKeyName = 'custom_slug';
    };
    expect($page->getSlugKeyName())->toBe('custom_slug');
});

it('returns slug key name from permalinkable config', function() {
    $page = Page::factory()->make(['permalink_slug' => null]);
    expect($page->getSlugKeyName())->toBe('permalink_slug');

    $page = new class extends Page
    {
        protected $permalinkable = ['permalink_slug'];
    };
    expect($page->getSlugKeyName())->toBe('permalink_slug');
});

it('returns slug key value', function() {
    $page = Page::factory()->create();

    expect($page->getSlugKey())->toBe($page->permalink_slug);
});

it('finds model by slug', function() {
    $page = Page::factory()->create();

    $foundPage = Page::make()->findSlug($page->permalink_slug);

    expect($foundPage->permalink_slug)->toBe($page->permalink_slug);
});
