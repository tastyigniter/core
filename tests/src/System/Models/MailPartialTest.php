<?php

namespace Igniter\Tests\System\Models;

use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Support\Facades\File;
use Igniter\System\Classes\MailManager;
use Igniter\System\Models\MailPartial;
use Illuminate\Support\Facades\View;

it('returns partial when found by code', function() {
    $mailManager = mock(MailManager::class);
    app()->instance(MailManager::class, $mailManager);
    $mailManager->shouldReceive('listRegisteredPartials')->andReturn(['_mail.test_partial' => 'test_path']);

    File::shouldReceive('get')->once()->andReturn("name = Test Partial\n===\ntext_content\n===\nhtml_content\n");
    View::shouldReceive('make->getPath')->andReturn('test_path');

    MailPartial::create(['code' => '_mail.test_partial']);
    $result = MailPartial::findOrMakePartial('_mail.test_partial');

    expect($result->code)->toBe('_mail.test_partial');
});

it('creates partial when not found by code', function() {
    $mailManager = mock(MailManager::class);
    app()->instance(MailManager::class, $mailManager);
    $mailManager->shouldReceive('listRegisteredPartials')->andReturn(['test_code' => 'test_path']);

    File::shouldReceive('get')->once()->andReturn("name = Test Partial\n===\ntext_content\n===\nhtml_content\n");
    View::shouldReceive('make->getPath')->andReturn('test_path');

    $result = MailPartial::findOrMakePartial('test_code');

    expect($result->code)->toBe('test_code')
        ->and($result->name)->toBe('Test Partial');
});

it('throws exception when filling from invalid code', function() {
    $mailManager = mock(MailManager::class);
    app()->instance(MailManager::class, $mailManager);
    $mailManager->shouldReceive('listRegisteredPartials')->andReturn([]);

    $partial = new MailPartial(['code' => 'test_code']);

    expect(fn() => $partial->fillFromCode())->toThrow(SystemException::class);
});

it('returns null when code is null', function() {
    $partial = new MailPartial;

    expect($partial->fillFromCode())->toBeNull();
});

it('fills partial from valid code', function() {
    $mailManager = mock(MailManager::class);
    app()->instance(MailManager::class, $mailManager);
    $mailManager->shouldReceive('listRegisteredPartials')->andReturn(['test_code' => 'test_path']);

    File::shouldReceive('get')->once()->andReturn("name = Test Partial\n===\ntext_content\n===\nhtml_content\n");
    View::shouldReceive('make->getPath')->andReturn('test_path');

    $partial = new MailPartial(['code' => 'test_code']);
    $partial->fillFromCode();

    expect($partial->name)->toBe('Test Partial')
        ->and($partial->html)->toBe('html_content')
        ->and($partial->text)->toBe('text_content');
});

it('creates partials if not exist', function() {
    MailPartial::create(['code' => 'valid_code']);
    $mailManager = mock(MailManager::class);
    app()->instance(MailManager::class, $mailManager);
    $mailManager->shouldReceive('listRegisteredPartials')->andReturn([
        'valid_code' => 'valid_path',
        'test_code' => 'test_path',
    ]);

    File::shouldReceive('get')->once()->andReturn("name = Test Partial\n===\ntext_content\n===\nhtml_content\n");
    View::shouldReceive('make->getPath')->andReturn('test_path');

    MailPartial::createPartials();

    $partial = MailPartial::where('code', 'test_code')->first();

    expect($partial)->not->toBeNull()
        ->and($partial->name)->toBe('Test Partial');
});

it('configures mail partial correctly', function() {
    $partial = new MailPartial;

    expect($partial->getTable())->toBe('mail_partials')
        ->and($partial->getKeyName())->toBe('partial_id')
        ->and($partial->getGuarded())->toEqual([])
        ->and($partial->timestamps)->toBeTrue()
        ->and($partial->getCasts())->toEqual([
            'partial_id' => 'int',
            'is_custom' => 'boolean',
        ]);
});
