<?php

namespace Igniter\Tests\System\Models;

use Igniter\Flame\Support\Facades\File;
use Igniter\System\Classes\MailManager;
use Igniter\System\Models\MailTemplate;
use Illuminate\Support\Facades\View;

it('returns variable options', function() {
    $mailManager = mock(MailManager::class);
    app()->instance(MailManager::class, $mailManager);
    $mailManager->shouldReceive('listRegisteredVariables')->andReturn(['var1' => 'Variable 1']);

    $result = MailTemplate::getVariableOptions();

    expect($result)->toHaveKey('var1', 'Variable 1');
});

it('return title attribute value', function() {
    $template = new MailTemplate(['label' => 'Test Subject']);
    expect($template->title)->toBe('Test Subject');
});

it('fills template from view on fetch', function() {
    File::shouldReceive('get')->once()->andReturn("subject = Test Subject\n===\ntext_content\n===\nhtml_content\n");
    View::shouldReceive('make->getPath')->andReturn('test_path');

    MailTemplate::flushEventListeners();
    $template = MailTemplate::create(['code' => 'test_code', 'is_custom' => false]);
    $template = MailTemplate::find($template->getKey());

    expect($template->subject)->toBe('Test Subject')
        ->and($template->body)->toBe('html_content')
        ->and($template->plain_body)->toBe('text_content');
});

it('fills template from content', function() {
    $template = new MailTemplate();
    $template->fillFromContent("subject = Test Subject\n===\ntext_content\n===\nhtml_content\n");

    expect($template->subject)->toBe('Test Subject')
        ->and($template->body)->toBe('html_content')
        ->and($template->plain_body)->toBe('text_content');
});

it('fills template from view', function() {
    File::shouldReceive('get')->once()->andReturn("subject = Test Subject\n===\ntext_content\n===\nhtml_content\n");
    View::shouldReceive('make->getPath')->andReturn('test_path');

    $template = new MailTemplate(['code' => 'test_code']);
    $template->fillFromView();

    expect($template->subject)->toBe('Test Subject')
        ->and($template->body)->toBe('html_content')
        ->and($template->plain_body)->toBe('text_content');
});

it('synchronizes all templates to the database', function() {
    $mailManager = mock(MailManager::class);
    app()->instance(MailManager::class, $mailManager);
    $mailManager->shouldReceive('listRegisteredLayouts')->andReturn(['test_code' => 'test_path']);
    $mailManager->shouldReceive('listRegisteredPartials')->andReturn(['test_code' => 'test_path']);
    $mailManager->shouldReceive('listRegisteredTemplates')->andReturn(['test_code' => 'Test Label']);

    File::shouldReceive('get')->andReturn("subject = Test Subject\n===\ntext_content\n===\nhtml_content\n");
    View::shouldReceive('make->getPath')->andReturn('test_path');

    MailTemplate::create(['code' => 'custom_code', 'is_custom' => true]);
    MailTemplate::create(['code' => 'existing_code']);

    MailTemplate::syncAll();

    $template = MailTemplate::where('code', 'test_code')->first();
    expect($template)->not->toBeNull()
        ->and($template->label)->toBe('Test Label')
        ->and(MailTemplate::where('code', 'existing_code')->exists())->toBeFalse();
});

it('returns template when found by code', function() {
    MailTemplate::create(['code' => 'test_code']);
    $result = MailTemplate::findOrMakeTemplate('test_code');

    expect($result->code)->toBe('test_code');
});

it('creates template when not found by code', function() {
    File::shouldReceive('get')->once()->andReturn("subject = Test Subject\n===\ntext_content\n===\nhtml_content\n");
    View::shouldReceive('make->getPath')->andReturn('test_path');

    $result = MailTemplate::findOrMakeTemplate('test_code');

    expect($result->code)->toBe('test_code')
        ->and($result->subject)->toBe('Test Subject');
});

it('returns list of all templates', function() {
    MailTemplate::create(['code' => 'test_code']);
    $mailManager = mock(MailManager::class);
    app()->instance(MailManager::class, $mailManager);
    $mailManager->shouldReceive('listRegisteredTemplates')->andReturn(['registered_code' => 'Registered Label']);

    $result = MailTemplate::listAllTemplates();

    expect($result)->toHaveKey('registered_code');
});

it('configures mail template correctly', function() {
    $template = new MailTemplate;

    expect($template->getTable())->toEqual('mail_templates')
        ->and($template->getKeyName())->toEqual('template_id')
        ->and($template->getGuarded())->toEqual([])
        ->and($template->getCasts())->toEqual([
            'template_id' => 'int',
            'layout_id' => 'integer',
        ])
        ->and($template->relation['belongsTo'])->toEqual([
            'layout' => [\Igniter\System\Models\MailLayout::class, 'foreignKey' => 'layout_id'],
        ])
        ->and($template->getAppends())->toEqual(['title'])
        ->and($template->timestamps)->toBeTrue();
});
