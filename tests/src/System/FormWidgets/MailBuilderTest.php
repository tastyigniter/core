<?php

declare(strict_types=1);

namespace Igniter\Tests\System\FormWidgets;

use Igniter\Admin\Classes\FormField;
use Igniter\Flame\Exception\FlashException;
use Igniter\System\Classes\MailBuilder\MailBlockManager;
use Igniter\System\Classes\MailManager;
use Igniter\System\FormWidgets\MailBuilder;
use Igniter\System\Models\Language;
use Igniter\System\Models\MailLayout;
use Igniter\System\Models\MailPartial;
use Igniter\System\Models\MailTemplate;
use Igniter\System\Models\MailTheme;
use Igniter\Tests\Fixtures\Controllers\TestController;
use Igniter\Tests\System\Fixtures\TestMailBlock;
use Illuminate\Validation\ValidationException;
use Throwable;

beforeEach(function() {
    $this->controller = resolve(TestController::class);
    $this->makeWidget = function(mixed $model, string $mode = 'template', mixed $value = null, string $field = 'blocks'): MailBuilder {
        $formField = new FormField($field, 'Design');
        $formField->displayAs('mailbuilder');
        $formField->arrayName = class_basename($model);
        $formField->value = $value;

        return new MailBuilder($this->controller, $formField, ['model' => $model, 'mode' => $mode]);
    };
});

// Settings models keep their instance in memory, so a saved mail theme would
// outlive the test's database rollback.
afterEach(function() {
    MailTheme::clearInternalCache();
});

it('rejects an invalid mode', function() {
    expect(fn() => ($this->makeWidget)(new MailTemplate, 'invalid'))->toThrow(FlashException::class);
});

it('renders with editor config', function() {
    resolve(MailBlockManager::class)->registerBlock(TestMailBlock::class);
    $widget = ($this->makeWidget)(new MailTemplate(['code' => 'igniter.cart::mail.order']));

    $html = $widget->render();
    $config = $widget->getEditorConfig();

    expect($html)->toContain('data-control="mail-builder"')
        ->and($html)->toContain('data-mail-builder-config')
        ->and($config['mode'])->toBe('template')
        ->and($config['handlers']['preview'])->toBe('mailbuilder::onPreview')
        ->and(array_column($config['blocks'], 'code'))->toContain('heading', 'test.extension::greeting')
        ->and(array_column($config['blocks'], 'code'))->not->toContain('content_slot')
        ->and($config['blockNames'])->toHaveKey('content_slot')
        ->and($config['theme'])->toHaveCount(18)
        ->and($config['lang'])->toHaveKey('undo')
        ->and(collect($config['blocks'])->firstWhere('code', 'legacy')['hidden'])->toBeTrue()
        ->and($widget->vars['blocks'])->toHaveCount(3);
});

it('loads saved blocks and starter blocks', function() {
    $saved = ($this->makeWidget)(new MailTemplate, 'template', json_encode([['id' => 'b1', 'code' => 'divider']]));
    $layout = ($this->makeWidget)(new MailLayout, 'layout', null, 'blocks');
    $partial = ($this->makeWidget)(new MailPartial, 'partial', null, 'blocks');

    expect(array_column($saved->getBlocks(), 'code'))->toBe(['divider'])
        ->and(array_column($layout->getBlocks(), 'code'))->toBe(['header', 'content_slot', 'footer'])
        ->and(array_column($partial->getBlocks(), 'code'))->toBe(['slot']);
});

it('imports existing content', function() {
    $template = MailTemplate::create(['code' => '_mail.custom', 'is_custom' => true, 'subject' => 'x', 'body' => "# Hello\n\n@php echo 1; @endphp"]);
    $widget = ($this->makeWidget)($template);

    expect(array_column($widget->getBlocks(), 'code'))->toBe(['heading', 'text'])
        ->and($widget->getEditorConfig()['notices'])->toBe([lang('igniter::system.mail_builder.notice_escaped_content')]);

    $layout = MailLayout::factory()->create(['code' => 'default', 'is_locked' => false]);
    $partial = MailPartial::firstOrCreate(['code' => 'button'], ['is_custom' => false, 'name' => 'Button']);
    $partial = MailPartial::find($partial->getKey());
    $partial->is_custom = false;
    $partial->fillFromCode();

    $layout = MailLayout::find($layout->getKey());
    $layout->fillFromCode();

    expect(array_column(($this->makeWidget)($layout, 'layout', null, 'blocks')->getBlocks(), 'code'))
        ->toBe(['header', 'content_slot', 'footer'])
        ->and(array_column(($this->makeWidget)($partial, 'partial', null, 'blocks')->getBlocks(), 'code'))
        ->toBe(['legacy']);
});

it('normalizes, validates and compiles the save value', function() {
    $template = new MailTemplate(['code' => '_mail.test']);
    $widget = ($this->makeWidget)($template);

    $blocks = $widget->getSaveValue(json_encode([['id' => 'b1', 'code' => 'text', 'settings' => ['content' => 'Hi']]]));

    expect($blocks)->toBe([['id' => 'b1', 'code' => 'text', 'settings' => ['content' => 'Hi', 'align' => 'left', 'visible_when' => null, 'visible_mode' => 'set']]])
        ->and($template->body)->toBe('<div class="mail-block"><p>Hi</p></div>')
        ->and($template->plain_body)->toBe('Hi');

    expect(fn() => $widget->getSaveValue(json_encode([['id' => 'b1', 'code' => 'heading', 'settings' => ['text' => '']]])))
        ->toThrow(ValidationException::class);
});

it('compiles layouts and partials when saving', function() {
    $layout = new MailLayout;
    ($this->makeWidget)($layout, 'layout', null, 'blocks')->getSaveValue([]);

    request()->request->add(['MailPartial' => ['params' => [['name' => 'code', 'label' => 'Code', 'sample' => 'X']]]]);
    $partial = new MailPartial;
    ($this->makeWidget)($partial, 'partial', null, 'blocks')->getSaveValue([
        ['code' => 'text', 'settings' => ['content' => 'Use {{ $code }}']],
    ]);

    expect($layout->layout)->toContain('{!! $body !!}')
        ->and($partial->html)->toContain('Use {{ $code }}');
});

it('previews templates with sample data', function() {
    resolve(MailManager::class)->registerMailTemplates(['_mail.test' => ['label' => 'Test', 'variables' => ['igniter::system.mail_variables.text_group_order']]]);
    $layout = MailLayout::factory()->create([
        'code' => 'preview_layout',
        'is_locked' => true,
        'layout' => '<html><head><style>{{ $custom_css }}</style></head><body>{!! $body !!}</body></html>',
        'plain_layout' => '{!! $body !!}',
    ]);
    $template = new MailTemplate(['code' => '_mail.test']);
    $widget = ($this->makeWidget)($template);

    request()->request->add([
        'blocks' => json_encode([
            ['id' => 'b1', 'code' => 'heading', 'settings' => ['text' => 'Hello {{ $site_name }}']],
            ['id' => 'b2', 'code' => 'button', 'settings' => ['label' => '', 'url' => 'bad']],
        ]),
        'subject' => 'Order {{ $order_number }}',
        'layout_id' => $layout->getKey(),
        'theme' => json_encode(['body_bg' => '#123456', 'text_color' => 'red']),
    ]);

    $result = $widget->onPreview();

    expect($result['subject'])->toBe('Order ['.lang('igniter::system.mail_variables.text_order_number').']')
        ->and($result['html'])->toContain('data-mb-block="b1"')
        ->and($result['html'])->toContain('#123456')
        ->and($result['text'])->toContain('Hello')
        ->and($result['errors'])->toHaveKeys(['blocks.b2.label', 'blocks.b2.url'])
        ->and($result['summaries'])->toHaveKey('b1', 'Hello {{ $site_name }}');
});

it('previews layouts and partials', function() {
    request()->request->add([
        'blocks' => json_encode([['id' => 'b1', 'code' => 'content_slot']]),
        'layout_css' => '.x { color: red; }',
    ]);
    $layout = ($this->makeWidget)(new MailLayout(['code' => 'test']), 'layout', null, 'blocks')->onPreview();

    request()->request->replace([
        'blocks' => json_encode([['id' => 'b1', 'code' => 'parameter', 'settings' => ['name' => 'promo']], ['id' => 'b2', 'code' => 'slot']]),
        'params' => json_encode([['name' => 'promo', 'label' => 'Promo', 'sample' => 'SAVE10']]),
    ]);
    $partial = ($this->makeWidget)(new MailPartial(['code' => 'promo']), 'partial', null, 'blocks')->onPreview();

    expect($layout['html'])->toContain(lang('igniter::system.mail_builder.text_sample_body'))
        ->and($layout['html'])->toContain('.x { color: red; }')
        ->and($partial['html'])->toContain('SAVE10')
        ->and($partial['html'])->toContain(lang('igniter::system.mail_builder.text_sample_slot'))
        ->and($partial['text'])->toContain('SAVE10');
});

it('reports preview render errors', function() {
    // A customised core partial that fails to render, used by the panel block.
    MailPartial::updateOrCreate(['code' => 'panel'], ['name' => 'Panel', 'is_custom' => true, 'html' => '{{ $missing_variable->call() }}']);
    request()->request->add([
        'blocks' => json_encode([['id' => 'b1', 'code' => 'panel', 'settings' => ['content' => 'Hi']]]),
    ]);

    $result = ($this->makeWidget)(new MailTemplate)->onPreview();

    expect($result)->toHaveKey('error')
        ->and($result['errors'])->toBe([]);
});

it('sends test messages of the draft', function() {
    config(['mail.default' => 'array']);
    request()->request->add([
        'email' => 'test@example.com',
        'blocks' => json_encode([['id' => 'b1', 'code' => 'text', 'settings' => ['content' => 'Draft body']]]),
    ]);

    $result = ($this->makeWidget)(new MailTemplate)->onSendTest();

    $sent = app('mailer')->getSymfonyTransport()->messages()->first()->getOriginalMessage();

    expect($result['message'])->toBe(sprintf(lang('igniter::system.mail_builder.alert_test_sent'), 'test@example.com'))
        ->and($sent->getTo()[0]->getAddress())->toBe('test@example.com')
        ->and($sent->getSubject())->toBe(lang('igniter::system.mail_builder.text_test_subject'))
        ->and($sent->getHtmlBody())->toContain('Draft body')
        ->and($sent->getTextBody())->toContain('Draft body');
});

it('validates the test message address', function() {
    request()->request->add(['email' => 'invalid']);

    expect(fn() => ($this->makeWidget)(new MailTemplate)->onSendTest())->toThrow(ValidationException::class);
});

it('saves the mail theme', function() {
    request()->request->add(['theme' => ['body_bg' => '#ABCDEF', 'unknown' => '#000000']]);

    $result = ($this->makeWidget)(new MailTemplate)->onSaveTheme();

    expect($result['theme']['body-bg'])->toBe('#abcdef')
        ->and(MailTheme::get('body_bg'))->toBe('#abcdef');

    request()->request->replace(['theme' => json_encode(['body_bg' => 'red'])]);

    expect(fn() => ($this->makeWidget)(new MailTemplate)->onSaveTheme())->toThrow(ValidationException::class);
});

it('reads posted partial parameters', function() {
    $partial = new MailPartial(['params' => [['name' => 'saved']]]);
    $widget = ($this->makeWidget)($partial, 'partial', null, 'blocks');

    expect($widget->makeContext()->params)->toBe([])
        ->and($widget->getEditorConfig()['variables'][0]['code'])->toBe('params')
        ->and($widget->getEditorConfig()['variables'][0]['variables'][0]['name'])->toBe('saved');

    request()->request->add(['params' => 'not-json']);
    expect(fn() => $widget->onPreview())->not->toThrow(Throwable::class);
});

it('skips compiling into models that are not mail records', function() {
    $model = new Language;
    $widget = ($this->makeWidget)($model);

    $widget->getSaveValue([['code' => 'divider']]);

    expect($model->getAttributes())->not->toHaveKey('body');
});

it('skips theme fields that are not mail theme colours', function() {
    $formField = new FormField('blocks', 'Design');
    $formField->arrayName = 'MailTemplate';
    $widget = new class($this->controller, $formField, ['model' => new MailTemplate]) extends MailBuilder
    {
        protected function loadThemeConfig(): array
        {
            return ['form' => ['fields' => ['body_bg' => ['label' => 'Body', 'group' => 'Group'], 'unknown' => ['label' => 'Unknown']]]];
        }
    };

    $theme = $widget->getEditorConfig()['theme'];

    expect($theme)->toHaveCount(1)
        ->and($theme[0])->toMatchArray(['name' => 'body_bg', 'label' => 'Body', 'group' => 'Group']);
});
