<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Classes\MailBuilder;

use Igniter\Flame\Exception\SystemException;
use Igniter\System\Classes\MailBuilder\CompileContext;
use Igniter\System\Classes\MailBuilder\MailBlockManager;
use Igniter\System\Classes\MailBuilder\MailBuilder;
use Igniter\System\Classes\MailManager;
use Igniter\System\Models\MailPartial;
use Illuminate\Validation\ValidationException;

beforeEach(function() {
    $this->builder = resolve(MailBuilder::class);
});

it('makes compile contexts', function() {
    MailPartial::create(['code' => 'custom_partial', 'name' => 'Custom partial', 'is_custom' => true]);

    $context = $this->builder->makeContext(CompileContext::MODE_PARTIAL, 'custom_partial', [['name' => 'url']], true);

    expect($context->mode)->toBe('partial')
        ->and($context->code)->toBe('custom_partial')
        ->and($context->preview)->toBeTrue()
        ->and($context->allowsPartial('button'))->toBeTrue()
        // A partial cannot include itself
        ->and($context->allowsPartial('custom_partial'))->toBeFalse()
        ->and($context->allowsVariable('url'))->toBeTrue()
        ->and($this->builder->getBlockManager())->toBeInstanceOf(MailBlockManager::class)
        ->and($this->builder->listPartials())->toHaveKey('custom_partial', 'Custom partial');

    expect(fn() => $this->builder->makeContext('invalid'))->toThrow(SystemException::class);
});

it('normalizes documents', function() {
    $context = $this->builder->makeContext(CompileContext::MODE_TEMPLATE);

    $blocks = $this->builder->normalize(json_encode([
        ['id' => 'b1', 'code' => 'heading', 'settings' => ['text' => 'Hi', 'extra' => 'dropped']],
        ['id' => 'b1', 'code' => 'text', 'settings' => 'invalid'],
        ['id' => 'bad id!', 'code' => 'divider'],
        ['code' => 'unknown.extension::block', 'settings' => ['a' => 1]],
        ['code' => 'header'],
        ['code' => 'bad code!'],
        'invalid',
    ]), $context);

    expect($blocks)->toHaveCount(4)
        ->and($blocks[0])->toBe(['id' => 'b1', 'code' => 'heading', 'settings' => ['text' => 'Hi', 'level' => 'h1', 'align' => 'left', 'visible_when' => null, 'visible_mode' => 'set']])
        ->and($blocks[1]['id'])->not->toBe('b1')
        ->and($blocks[1]['settings'])->toBe(['content' => 'Write your message here.', 'align' => 'left', 'visible_when' => null, 'visible_mode' => 'set'])
        ->and($blocks[2]['code'])->toBe('divider')
        ->and($blocks[3])->toMatchArray(['code' => 'unknown.extension::block', 'settings' => ['a' => 1], 'unavailable' => true])
        ->and($this->builder->normalize('not json', $context))->toBe([]);
});

it('keeps unique blocks once and adds required blocks', function() {
    $context = $this->builder->makeContext(CompileContext::MODE_LAYOUT);

    $blocks = $this->builder->normalize([
        ['code' => 'header'],
        ['code' => 'header'],
    ], $context);

    expect(array_column($blocks, 'code'))->toBe(['header', 'content_slot']);
});

it('validates block settings', function() {
    $context = $this->builder->makeContext(CompileContext::MODE_TEMPLATE);
    $blocks = $this->builder->normalize([
        ['id' => 'b1', 'code' => 'heading', 'settings' => ['text' => '', 'level' => 'h9']],
        ['id' => 'b2', 'code' => 'unknown::block'],
        ['id' => 'b3', 'code' => 'text', 'settings' => ['content' => 'Fine']],
    ], $context);

    try {
        $this->builder->validate($blocks, $context);
        $this->fail('Validation should fail');
    } catch (ValidationException $validationException) {
        expect($validationException->errors())->toHaveKeys(['blocks.b1.text', 'blocks.b1.level'])
            ->and($validationException->errors())->not->toHaveKey('blocks.b3.content');
    }

    $this->builder->validate([$blocks[2]], $context);
});

it('compiles templates to html and text', function() {
    $context = $this->builder->makeContext(CompileContext::MODE_TEMPLATE);
    $blocks = $this->builder->normalize([
        ['id' => 'b1', 'code' => 'heading', 'settings' => ['text' => 'Hello {{ $site_name }}']],
        ['id' => 'b2', 'code' => 'divider'],
        ['id' => 'b3', 'code' => 'unknown::block'],
        ['id' => 'b4', 'code' => 'image', 'settings' => ['src' => 'javascript:alert(1)']],
    ], $context);

    expect($this->builder->compileHtml($blocks, $context))
        ->toBe('<div class="mail-block"><h1 style="text-align: left">Hello {{ $site_name }}</h1></div>'
            ."\n".'<div class="mail-block"><hr style="border: 0; border-top: 1px solid #edeff2; margin: 24px 0;"></div>')
        ->and($this->builder->compileText($blocks, $context))->toBe("Hello {{ \$site_name }}\n\n----------");
});

it('marks blocks in preview mode', function() {
    $context = $this->builder->makeContext(CompileContext::MODE_TEMPLATE, preview: true);
    $blocks = $this->builder->normalize([['id' => 'b1', 'code' => 'divider']], $context);

    expect($this->builder->compileHtml($blocks, $context))->toContain('<div class="mail-block" data-mb-block="b1">');
});

it('compiles layouts into a mail document', function() {
    $context = $this->builder->makeContext(CompileContext::MODE_LAYOUT, preview: true);
    $blocks = $this->builder->normalize([
        ['id' => 'b1', 'code' => 'header', 'settings' => ['text' => 'Site', 'show_logo' => false]],
        ['id' => 'b2', 'code' => 'divider'],
        ['id' => 'b3', 'code' => 'content_slot'],
    ], $context);

    $html = $this->builder->compileHtml($blocks, $context);

    expect($html)->toStartWith('<!DOCTYPE html')
        ->and($html)->toContain('<style type="text/css">{!! $custom_css !!}{!! $layout_css !!}</style>')
        ->and($html)->toContain('<tbody data-mb-block="b1"> @partial(\'header\')Site @endpartial</tbody>')
        ->and($html)->toContain('<tbody data-mb-block="b2"><tr><td class="content-cell"><hr')
        ->and($html)->toContain('{!! $body !!}')
        ->and($this->builder->compileText($blocks, $context))->toBe("Site\n\n----------\n\n{!! \$body !!}");
});

it('compiles subjects', function() {
    resolve(MailManager::class)->registerMailTemplates(['_mail.order_test' => [
        'label' => 'Order test',
        'variables' => ['igniter::system.mail_variables.text_group_order'],
    ]]);
    $context = $this->builder->makeContext(CompileContext::MODE_TEMPLATE, '_mail.order_test');

    expect($this->builder->compileSubject('Order {{ $order_number }} {site_name} {unknown} @php {{ x() }}', $context))
        ->toBe('Order {{ $order_number }} {{ $site_name }} &#123;unknown&#125; &#64;php &#123;&#123; x() &#125;&#125;');
});

it('accepts compiled blade the writer produces', function(string $blade) {
    $this->builder->assertSafe($blade);

    expect(true)->toBeTrue();
})->with([
    ['<p>{{ $order_number }}</p>'],
    ["{{ \$order_menu['menu_name'] }}"],
    ['{!! $body !!}{!! $slot !!}'],
    ["{!! \$order_menu['menu_options'] !!}"],
    ["@partial('button', ['url' => base64_decode('aHR0cA==') . \$order_number, 'type' => \$item['x']])Go@endpartial"],
    ["@partial('panel')x@endpartial"],
    ['@foreach($order_menus as $order_menu)x@endforeach'],
    ['@if(!empty($site_logo))x@endif'],
]);

it('rejects blade the writer does not produce', function(string $blade) {
    expect(fn() => $this->builder->assertSafe($blade))->toThrow(SystemException::class);
})->with([
    ['{{ phpinfo() }}'],
    ['{!! $order_number !!}'],
    ['@php echo 1; @endphp'],
    ['<?php echo 1; ?>'],
    ["@partial('button', ['url' => system('id')])x@endpartial"],
    ["@partial('button', ['url' => base64_decode('a') . system('id')])x@endpartial"],
    ['@include("x")'],
    ['{{-- comment --}}'],
    ['x !!} y'],
]);

it('shows blocks only when their condition variable is set or empty', function() {
    $context = $this->builder->makeContext(CompileContext::MODE_TEMPLATE);
    $blocks = $this->builder->normalize([
        ['id' => 'b1', 'code' => 'divider', 'settings' => ['visible_when' => 'site_name']],
        ['id' => 'b2', 'code' => 'divider', 'settings' => ['visible_when' => 'site_name', 'visible_mode' => 'empty']],
        ['id' => 'b3', 'code' => 'divider', 'settings' => ['visible_when' => 'not_allowed']],
    ], $context);

    $html = $this->builder->compileHtml($blocks, $context);
    $text = $this->builder->compileText($blocks, $context);

    expect($html)->toContain('<div class="mail-block"> @if(!empty($site_name))<hr')
        ->and($html)->toContain('<div class="mail-block"> @if(empty($site_name))<hr')
        ->and(substr_count($html, '@if('))->toBe(2)
        ->and($text)->toContain('@if(!empty($site_name))----------')
        ->and($text)->toContain('@if(empty($site_name))----------');
});

it('always shows structural blocks', function() {
    $context = $this->builder->makeContext(CompileContext::MODE_LAYOUT);
    $blocks = $this->builder->normalize([
        ['id' => 'b1', 'code' => 'content_slot', 'settings' => ['visible_when' => 'site_name']],
    ], $context);

    expect($blocks[0]['settings'])->toBe([])
        ->and($this->builder->compileHtml($blocks, $context))->not->toContain('@if(');
});

it('limits template variables to the registered groups', function() {
    $manager = resolve(MailManager::class);
    $manager->registerMailTemplates([
        '_mail.grouped' => ['label' => 'Grouped', 'variables' => ['igniter::system.mail_variables.text_group_order']],
        '_mail.ungrouped' => 'Ungrouped',
    ]);

    $grouped = $this->builder->makeContext(CompileContext::MODE_TEMPLATE, '_mail.grouped');
    $ungrouped = $this->builder->makeContext(CompileContext::MODE_TEMPLATE, '_mail.ungrouped');
    $layout = $this->builder->makeContext(CompileContext::MODE_LAYOUT, '_mail.grouped');

    expect($manager->listRegisteredTemplates())->toHaveKey('_mail.grouped', 'Grouped')
        ->and($manager->getTemplateVariableGroups('_mail.grouped'))->toBe([MailManager::GLOBAL_VARIABLES, 'igniter::system.mail_variables.text_group_order'])
        ->and($manager->getTemplateVariableGroups('_mail.ungrouped'))->toBe([MailManager::GLOBAL_VARIABLES])
        ->and($grouped->allowsVariable('order_number'))->toBeTrue()
        ->and($grouped->allowsVariable('site_name'))->toBeTrue()
        ->and($grouped->allowsVariable('reservation_number'))->toBeFalse()
        ->and($ungrouped->allowsVariable('order_number'))->toBeFalse()
        ->and($layout->allowsVariable('order_number'))->toBeFalse()
        ->and(array_column($grouped->variables->groups(), 'key'))->toBe([MailManager::GLOBAL_VARIABLES, 'igniter::system.mail_variables.text_group_order']);
});
