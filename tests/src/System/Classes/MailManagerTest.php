<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Classes;

use Igniter\System\Classes\MailManager;
use Igniter\System\Models\MailLayout;
use Igniter\System\Models\MailTemplate;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

it('renders mail templates', function() {
    $manager = resolve(MailManager::class);
    $template = $manager->getTemplate('_mail.test_template');

    expect((string)$manager->renderTextTemplate($template))
        ->toContain('PLAIN TEXT CONTENT')
        ->and((string)$manager->renderTemplate($template))
        ->toContain('HTML CONTENT')
        ->and((string)$manager->renderView($template->subject))
        ->toContain('Test mail template subject');
});

it('applies mailer config values correctly', function($driver) {
    setting()->set('protocol', $driver);

    $manager = resolve(MailManager::class);
    $manager->applyMailerConfigValues();

    expect(config('mail.default'))->toBe($driver);
})->with([
    ['smtp'],
    ['mailgun'],
    ['postmark'],
    ['ses'],
]);

it('fetches and caches template correctly', function() {
    $manager = resolve(MailManager::class);
    $template = MailTemplate::create([
        'code' => 'test',
        'subject' => 'Test subject',
        'body' => 'Test body',
    ]);

    $result = $manager->getTemplate('test');
    expect($result->getKey())->toBe($template->getKey())
        ->and($manager->getTemplate('test')->getKey())->toBe($template->getKey());
});

it('renders template with layout', function() {
    $manager = resolve(MailManager::class);
    $template = MailTemplate::create([
        'code' => 'test',
        'subject' => 'Test subject',
        'body' => 'Test body',
    ]);
    $template->layout = MailLayout::factory()->create([
        'code' => 'test_layout',
        'layout' => '{{ $layout_css }} Test layout content {!! $body !!}',
        'layout_css' => 'layout css',
    ]);

    $result = $manager->renderTemplate($template);
    expect($result->toHtml())->toBe("layout css Test layout content <p>Test body</p>\n");
});

it('renders text template with layout', function() {
    $manager = resolve(MailManager::class);
    $template = MailTemplate::create([
        'code' => 'test',
        'subject' => 'Test subject',
        'body' => 'plain body content',
        'plain_body' => '', // will use body content
    ]);
    $template->layout = MailLayout::factory()->create([
        'code' => 'test_layout',
        'layout' => '{{ $layout_css }} Test layout content {!! $body !!}',
        'plain_layout' => 'plain layout content {!! $body !!}',
    ]);

    $result = $manager->renderTextTemplate($template);
    expect($result)->toBeInstanceOf(HtmlString::class)
        ->and($result->toHtml())->toBe('plain layout content plain body content');
});

it('renders missing partial correctly', function() {
    $manager = resolve(MailManager::class);
    $manager->startPartial('test_partial');

    expect($manager->renderPartial()->toHtml())->toBe('<!-- Missing partial: test_partial -->');

    // Clear output buffer
    new HtmlString(trim(ob_get_clean()));
});

it('loads and returns registered layouts', function() {
    $manager = resolve(MailManager::class);

    $result = $manager->listRegisteredLayouts();
    expect($result)->toBe(['default' => 'igniter.system::_mail.layouts.default']);
});

it('loads and returns registered templates', function() {
    $manager = resolve(MailManager::class);

    $result = $manager->listRegisteredTemplates();
    expect($result)->not()->toBeEmpty();
});

it('loads and returns registered variables', function() {
    $manager = resolve(MailManager::class);

    $result = $manager->listRegisteredVariables();
    expect($result)->not()->toBeEmpty();
});

it('registers custom blade directives when rendering view', function() {
    $manager = resolve(MailManager::class);
    Blade::shouldReceive('directive')->andReturnUsing(function($name, $callback) {
        $callback(null);

        return in_array($name, ['partial', 'endpartial']);
    });
    Blade::shouldReceive('render')->andReturn('rendered view');

    $template = $manager->getTemplate('_mail.test_template');

    expect((string)$manager->renderView($template->subject))->toBeString();
});
