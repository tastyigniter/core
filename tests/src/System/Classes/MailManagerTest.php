<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Classes;

use Igniter\System\Classes\MailManager;
use Igniter\System\Models\MailLayout;
use Igniter\System\Models\MailTemplate;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
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
    Blade::shouldReceive('directive')->andReturnUsing(function($name, $callback): bool {
        $callback('');

        return in_array($name, ['partial', 'endpartial']);
    });
    Blade::shouldReceive('render')->andReturn('rendered view');

    $template = $manager->getTemplate('_mail.test_template');

    expect((string)$manager->renderView($template->subject))->toBeString();
});

it('renders builder subjects safely', function() {
    $manager = resolve(MailManager::class);
    $manager->registerMailTemplates(['_mail.test' => ['label' => 'Test', 'variables' => ['igniter::system.mail_variables.text_group_order']]]);
    $template = new MailTemplate(['code' => '_mail.test', 'subject' => 'Order {{ $order_number }} & {{ phpinfo() }}']);
    $template->blocks = [];

    expect($manager->renderSubject($template, ['order_number' => '42']))->toBe('Order 42 & {{ phpinfo() }}');

    $legacy = new MailTemplate(['subject' => 'Hi {{ $name }}']);
    expect($manager->renderSubject($legacy, ['name' => 'A & B']))->toBe('Hi A & B');
});

it('renders drafts with theme overrides', function() {
    $manager = resolve(MailManager::class);
    $layout = new MailLayout([
        'layout' => '<style>{{ $custom_css }}{{ $layout_css }}</style>{!! $body !!}',
        'plain_layout' => 'Layout: {!! $body !!} &amp;',
        'layout_css' => '</style><script>',
    ]);

    $template = new MailTemplate(['subject' => 'Subject', 'body' => 'Body', 'plain_body' => 'Text']);
    $template->setRelation('layout', $layout);

    $result = $manager->renderDraft($template, [], 'body { color: #123456; }');

    expect($result['subject'])->toBe('Subject')
        ->and($result['html'])->toContain('#123456')
        ->and($result['html'])->not->toContain('<script>')
        ->and($result['text'])->toBe('Layout: Text &')
        ->and($manager->renderLayoutDraft($layout)['html'])->toContain(lang('igniter::system.mail_builder.text_sample_body'));
});

it('renders partial drafts', function() {
    $result = resolve(MailManager::class)->renderPartialDraft('<b>{{ $title }}</b>{!! $slot !!}', '{{ $title }}: {!! $slot !!}', ['title' => 'A & B'], 'p { color: red; }');

    expect($result['subject'])->toBe('')
        ->and($result['html'])->toContain('<b>A &amp; B</b>'.e(lang('igniter::system.mail_builder.text_sample_slot')))
        ->and($result['html'])->toContain('p { color: red; }')
        ->and($result['text'])->toBe('A & B: '.lang('igniter::system.mail_builder.text_sample_slot'));
});

it('builds sample data from registered variables', function() {
    Event::listen('system.mail.sampleData', fn(?string $code, array $data) => $code === '_mail.test' ? ['order_number' => '#1001'] : null);

    $data = resolve(MailManager::class)->sampleData('_mail.test', [['name' => 'promo', 'label' => 'Promo'], ['name' => 'code', 'sample' => 'X1'], ['label' => 'No name']]);

    expect($data['order_number'])->toBe('#1001')
        ->and($data['first_name'])->toBe('['.lang('igniter::system.mail_variables.text_first_name').']')
        ->and($data['order_menus'])->toHaveCount(2)
        ->and($data['order_menus'][1]['menu_quantity'])->toBe(2)
        ->and($data['order_menus'][0]['menu_price'])->toBe('9.50')
        ->and($data['order_menus'][0]['menu_options'])->toBe('')
        ->and($data['order_menus'][0]['menu_name'])->toBe(lang('igniter::system.mail_variables.text_menu_name').' 1')
        ->and($data['promo'])->toBe('[Promo]')
        ->and($data['code'])->toBe('X1');
});

it('resolves the mail logo url', function() {
    $manager = resolve(MailManager::class);

    setting()->set('mail_logo', '');

    expect($manager->resolveLogoUrl())->toBe('')
        ->and($manager->resolveLogoUrl(['site_logo' => ['invalid']]))->toBe('')
        ->and($manager->resolveLogoUrl(['site_logo' => 'logo.png']))->not->toBe('');

    setting()->set('mail_logo', 'mail-logo.png');

    expect($manager->resolveLogoUrl(['site_logo' => 'logo.png']))->not->toBe('');
});
