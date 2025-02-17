<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Database\Relations;

use Igniter\Admin\Models\Status;
use Igniter\System\Models\Page;

it('associates and dissociates model correctly', function() {
    $status = new class(['status_name' => 'Test']) extends Status
    {
        public $relation = ['belongsTo' => ['page' => [Page::class]]];
    };
    $status->save();

    $page = Page::factory()->create();
    $builder = $status->page();
    $builder->add($page);

    expect($status->page_id)->toBe($builder->getSimpleValue());

    $builder->remove($page);

    expect($status->page_id)->toBeNull()
        ->and($builder->getOtherKey())->toBe('page_id');
});

it('sets simple value with null', function() {
    $status = new class(['status_name' => 'Test']) extends Status
    {
        public $relation = [
            'belongsTo' => [
                'page' => [
                    Page::class, 'default' => ['title' => 'Title'],
                ],
            ],
        ];
    };
    $status->save();
    $status->page()->setSimpleValue(null);

    expect($status->page_id)->toBeNull();
});

it('sets simple value with model instance', function() {
    $status = new class(['status_name' => 'Test']) extends Status
    {
        public $relation = ['belongsTo' => ['page' => [Page::class]]];
    };
    $status->save();

    $page = Page::factory()->create();
    $status->page()->setSimpleValue($page);

    expect($status->page_id)->toBe($page->page_id);
});

it('sets simple value with non-existent model', function() {
    Page::flushEventListeners();
    $status = new class(['status_name' => 'Test']) extends Status
    {
        public $relation = ['belongsTo' => ['page' => [Page::class]]];
    };
    $status->save();

    $page = Page::factory()->make();
    $status->page()->setSimpleValue($page);
    $page->save();

    expect($status->page_id)->toBe($page->page_id);
});

it('sets simple value with foreign key', function() {
    $status = new class(['status_name' => 'Test']) extends Status
    {
        public $relation = ['belongsTo' => ['page' => [Page::class]]];
    };
    $status->save();

    $page = Page::factory()->create();
    $status->page()->setSimpleValue($page->page_id);

    expect($status->page_id)->toBe($page->page_id);
});
