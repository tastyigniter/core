<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\Traits;

use Igniter\Main\Traits\UsesPage;
use Igniter\System\Models\Page;

it('returns static page from cache if exists', function() {
    $object = new class
    {
        use UsesPage;
    };
    $page = Page::factory()->create();

    expect($object->findStaticPage($page->getKey()))->not()->toBeNull();
});

it('returns empty string if static page not found for permalink', function() {
    $object = new class
    {
        use UsesPage;
    };
    $page = Page::factory()->create();

    expect($object->getStaticPagePermalink($page->getKey()))->toBe($page->permalink_slug);
});

it('fetches and caches theme page options if not in cache', function() {
    $object = new class
    {
        use UsesPage;
    };

    expect($object::getThemePageOptions())->toBe($object::getThemePageOptions());
});

it('fetches and caches static page options if not in cache', function() {
    $object = new class
    {
        use UsesPage;
    };

    expect($object::getStaticPageOptions())->toBe($object::getStaticPageOptions());
});
