<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Models;

use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Support\Facades\File;
use Igniter\System\Classes\MailManager;
use Igniter\System\Models\Concerns\Switchable;
use Igniter\System\Models\Language;
use Igniter\System\Models\MailLayout;
use Igniter\System\Models\MailTemplate;
use Illuminate\Support\Facades\View;

it('returns dropdown options for mail layouts', function() {
    $layout = MailLayout::factory()->create(['name' => 'Test Layout']);
    $result = MailLayout::getDropdownOptions();

    expect($result)->toHaveKey($layout->layout_id, 'Test Layout');
});

it('returns cached list of codes', function() {
    MailLayout::factory()->create(['code' => 'test_code']);

    $result = MailLayout::listCodes();

    expect($result)->toBe(MailLayout::listCodes());
});

it('returns id from code', function() {
    MailLayout::$codeCache = null;
    $layout = MailLayout::factory()->create(['code' => 'test_code']);
    $result = MailLayout::getIdFromCode('test_code');

    expect($result)->toBe($layout->layout_id);
});

it('throws exception when filling from invalid code', function() {
    $mailManager = mock(MailManager::class);
    app()->instance(MailManager::class, $mailManager);
    $mailManager->shouldReceive('listRegisteredLayouts')->andReturn([]);

    $layout = new MailLayout(['code' => 'invalid_code']);

    expect(fn() => $layout->fillFromCode())->toThrow(SystemException::class);
});

it('returns null when code is null', function() {
    $layout = new MailLayout;

    expect($layout->fillFromCode())->toBeNull();
});

it('fills layout from valid code', function() {
    $mailManager = mock(MailManager::class);
    app()->instance(MailManager::class, $mailManager);
    $mailManager->shouldReceive('listRegisteredLayouts')->andReturn(['test_code' => 'test_path']);

    $layout = new MailLayout(['code' => 'test_code']);
    File::shouldReceive('get')->once()->andReturn("name = Hey\n===\ntext_content\n===\nhtml_content\n");
    View::shouldReceive('make->getPath')->andReturn('test_path');

    $layout->fillFromCode();

    expect($layout->name)->toBe('Hey')
        ->and($layout->layout)->toBe('html_content')
        ->and($layout->plain_layout)->toBe('text_content');
});

it('fills layout from valid code on fetch', function() {
    $mailManager = mock(MailManager::class);
    app()->instance(MailManager::class, $mailManager);
    $mailManager->shouldReceive('listRegisteredLayouts')->andReturn(['test_code' => 'test_path']);

    new MailLayout(['code' => 'test_code']);
    File::shouldReceive('get')->once()->andReturn("name = Hey\n===\ntext_content\n===\nhtml_content\n");
    View::shouldReceive('make->getPath')->andReturn('test_path');

    MailLayout::flushEventListeners();
    $layout = MailLayout::factory()->create(['code' => 'test_code', 'is_locked' => false]);
    $layout = MailLayout::find($layout->getKey());

    expect($layout->name)->toBe('Hey')
        ->and($layout->layout)->toBe('html_content')
        ->and($layout->plain_layout)->toBe('text_content');
});

it('creates layouts if not exist', function() {
    MailLayout::factory()->create(['code' => 'valid_code']);
    $mailManager = mock(MailManager::class);
    app()->instance(MailManager::class, $mailManager);
    $mailManager->shouldReceive('listRegisteredLayouts')->andReturn([
        'valid_code' => 'valid_path',
        'test_code' => 'test_path',
    ]);

    File::shouldReceive('get')->andReturn('file_content');
    View::shouldReceive('make->getPath')->andReturn('test_path');

    MailLayout::createLayouts();

    $layout = MailLayout::where('code', 'test_code')->first();
    expect($layout)->not->toBeNull()
        ->and($layout->name)->toBe('???');
});

it('configures mail layout model correctly', function() {
    $layout = new MailLayout;

    expect(class_uses_recursive($layout))
        ->toContain(Switchable::class)
        ->and($layout->getTable())->toBe('mail_layouts')
        ->and($layout->getKeyName())->toBe('layout_id')
        ->and($layout->timestamps)->toBeTrue()
        ->and($layout->getCasts())->toEqual([
            'layout_id' => 'int',
            'language_id' => 'integer',
            'status' => 'boolean',
            'is_locked' => 'boolean',
        ])
        ->and($layout->relation['hasMany'])->toEqual([
            'templates' => [MailTemplate::class, 'foreignKey' => 'layout_id'],
        ])
        ->and($layout->relation['belongsTo'])->toEqual([
            'language' => Language::class,
        ]);
});
