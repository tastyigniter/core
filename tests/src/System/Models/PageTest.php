<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Models;

use Igniter\Flame\Database\Traits\HasPermalink;
use Igniter\System\Models\Concerns\Switchable;
use Igniter\System\Models\Language;
use Igniter\System\Models\Page;

it('returns dropdown options for enabled pages', function() {
    Page::factory()->hidden()->create(['title' => 'Test Page', 'permalink_slug' => 'test-page', 'language_id' => 1, 'status' => 1]);

    $result = Page::getDropdownOptions();

    expect($result)->toHaveKey('test-page', 'Test Page');
});

it('configures page model correctly', function() {
    $page = new Page;

    expect(class_uses_recursive($page))
        ->toContain(HasPermalink::class)
        ->toContain(Switchable::class)
        ->and($page->getTable())->toBe('pages')
        ->and($page->getKeyName())->toBe('page_id')
        ->and($page->timestamps)->toBeTrue()
        ->and($page->getGuarded())->toBe([])
        ->and($page->getCasts())->toEqual([
            'page_id' => 'int',
            'language_id' => 'integer',
            'metadata' => 'json',
        ])
        ->and($page->relation['belongsTo'])->toEqual([
            'language' => Language::class,
        ])
        ->and($page->permalinkable())->toEqual([
            'permalink_slug' => [
                'source' => 'title',
            ],
        ]);
});
