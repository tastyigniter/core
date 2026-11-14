<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Classes\MailBuilder;

use Igniter\Flame\Mail\MailParser;
use Igniter\Flame\Support\Facades\File;
use Igniter\System\Classes\MailBuilder\BaseMailBlock;
use Igniter\System\Classes\MailBuilder\BladeFragment;
use Igniter\System\Classes\MailBuilder\BladeWriter;
use Igniter\System\Classes\MailBuilder\CompileContext;
use Igniter\System\Classes\MailBuilder\MailBlockManager;
use Igniter\System\Classes\MailBuilder\MailBuilder;
use Igniter\System\Classes\MailBuilder\MailImporter;
use Igniter\System\Classes\MailManager;
use Illuminate\Support\Facades\View;

beforeEach(function() {
    $this->builder = resolve(MailBuilder::class);
    $this->importer = resolve(MailImporter::class);
});

it('splits content into chunks keeping blade blocks together', function() {
    $chunks = MailImporter::splitChunks("# Title\r\n\r\nText\n\n@partial('panel')\nLine one\n\nLine two\n@endpartial\n\n\nLast");

    expect($chunks)->toBe(['# Title', 'Text', "@partial('panel')\nLine one\n\nLine two\n@endpartial", 'Last'])
        ->and(MailImporter::splitChunks("<table>\n\n</table>", false))->toBe(["<table>\n\n</table>"])
        ->and(MailImporter::splitChunks('  ', false))->toBe([]);
});

it('imports template markdown into blocks', function() {
    resolve(MailManager::class)->registerMailTemplates(['_mail.import_test' => [
        'label' => 'Import test',
        'variables' => ['igniter::system.mail_variables.text_group_order'],
    ]]);
    $context = $this->builder->makeContext(CompileContext::MODE_TEMPLATE, '_mail.import_test');
    $source = implode("\n\n", [
        '## Hello {{ $first_name }}',
        'Thanks for your order, email me at info@example.com.',
        '---',
        '![Logo](https://example.com/logo.png)',
        "@partial('button', ['url' => \$order_view_url, 'type' => 'success'])\nView order\n@endpartial",
        "@partial('panel')\n    A note\n@endpartial",
        "@partial('promotion')\nSave 10%\n@endpartial",
        "@partial('subcopy')\nSmall print\n@endpartial",
        "@partial('table')\n| Item | Qty | Price |\n|:-----|:---:|------:|\n| Pizza | 2 | {{ \$order_number }} |\n@endpartial",
        "@partial('button', ['url' => 'https://example.com', 'align' => 'left'])\nGo\n@endpartial",
    ]);

    $result = $this->importer->import($source, $context);

    expect(array_column($result['blocks'], 'code'))->toBe(['heading', 'text', 'divider', 'image', 'button', 'panel', 'promotion', 'subcopy', 'table', 'button'])
        ->and($result['blocks'][0]['settings'])->toMatchArray(['text' => 'Hello {{ $first_name }}', 'level' => 'h2'])
        ->and($result['blocks'][3]['settings'])->toMatchArray(['src' => 'https://example.com/logo.png', 'alt' => 'Logo'])
        ->and($result['blocks'][4]['settings'])->toMatchArray(['label' => 'View order', 'url' => '{{ $order_view_url }}', 'style' => 'success'])
        ->and($result['blocks'][5]['settings'])->toMatchArray(['content' => 'A note'])
        ->and($result['blocks'][6]['settings'])->toMatchArray(['content' => 'Save 10%'])
        ->and($result['blocks'][7]['settings'])->toMatchArray(['content' => 'Small print'])
        ->and($result['blocks'][8]['settings']['table'])->toBe([
            'columns' => [['label' => 'Item', 'align' => 'left'], ['label' => 'Qty', 'align' => 'center'], ['label' => 'Price', 'align' => 'right']],
            'rows' => [['Pizza', '2', '{{ $order_number }}']],
        ])
        ->and($result['blocks'][9]['settings'])->toMatchArray(['align' => 'left'])
        ->and($result['notices'])->toBe([]);
});

it('imports unsupported custom content as escaped text', function() {
    $context = $this->builder->makeContext(CompileContext::MODE_TEMPLATE);

    $result = $this->importer->import(implode("\n\n", [
        '@foreach($order_menus as $menu) {{ $menu["name"] }} @endforeach',
        "@partial('unknown_partial')\nx\n@endpartial",
        "@partial('button', ['url' => route('x')])\nGo\n@endpartial",
        "@partial('button', ['type' => 'primary'])\nGo\n@endpartial",
        "@partial('panel')\n{{ phpinfo() }}\n@endpartial",
        "@partial('panel', ['title' => 'Note'])\nx\n@endpartial",
        "@partial('table')\nNot a table\n@endpartial",
        "@partial('table')\n| A |\n|---|\nno pipes\n@endpartial",
    ]), $context);

    expect(array_column($result['blocks'], 'code'))->toBe(['text', 'text', 'text', 'text', 'text', 'text', 'text', 'text'])
        ->and($result['blocks'][0]['settings']['content'])->toContain('@foreach')
        ->and($result['notices'])->toBe([lang('igniter::system.mail_builder.notice_escaped_content')]);
});

it('imports unsupported view content as legacy references', function() {
    $context = $this->builder->makeContext(CompileContext::MODE_TEMPLATE);
    $source = "Intro\n\n@foreach(\$order_menus as \$menu)\n{{ \$menu['name'] }}\n@endforeach";

    $result = $this->importer->import($source, $context, '_mail.test_template');

    expect(array_column($result['blocks'], 'code'))->toBe(['text', 'legacy'])
        ->and($result['blocks'][1]['settings'])->toBe([
            'view' => '_mail.test_template',
            'segment' => 1,
            'hash' => sha1("@foreach(\$order_menus as \$menu)\n{{ \$menu['name'] }}\n@endforeach"),
        ])
        ->and($result['notices'])->toBe([lang('igniter::system.mail_builder.notice_legacy_blocks')]);
});

it('imports partials', function() {
    $context = $this->builder->makeContext(CompileContext::MODE_PARTIAL, 'test');

    expect(array_column($this->importer->import('{{ $slot }}', $context)['blocks'], 'code'))->toBe(['slot'])
        ->and($this->importer->import('<div>{{ $slot }}</div>', $context)['blocks'][0])
        ->toMatchArray(['code' => 'text', 'settings' => ['content' => '{{ $slot }}', 'align' => 'left', 'visible_when' => null, 'visible_mode' => 'set']])
        ->and($this->importer->import('<div>{{ $slot }}</div>', $context, 'view')['blocks'][0]['code'])->toBe('legacy');
});

it('rebuilds layouts from blocks', function() {
    $context = $this->builder->makeContext(CompileContext::MODE_LAYOUT);
    $source = File::get(View::make('igniter.system::_mail.layouts.default')->getPath());
    $html = MailParser::parse($source)['html'];

    $result = $this->importer->import($html, $context, 'igniter.system::_mail.layouts.default');

    expect(array_column($result['blocks'], 'code'))->toBe(['header', 'content_slot', 'footer'])
        ->and($result['blocks'][0]['settings']['show_logo'])->toBeTrue()
        ->and($result['blocks'][2]['settings']['content'])->toBe('© '.date('Y').' {{ $site_name }}. All rights reserved.')
        // Registered layouts are rebuilt without a notice, customized ones get one
        ->and($result['notices'])->toBe([])
        ->and($this->importer->import($html, $context)['notices'])->toBe([lang('igniter::system.mail_builder.notice_layout_rebuilt')])
        ->and(array_column($this->importer->import('{!! $body !!}', $context)['blocks'], 'code'))->toBe(['content_slot']);
});

it('compiles legacy references from registered views only', function() {
    $manager = resolve(MailManager::class);
    $manager->registerMailTemplates(['_mail.test_template' => 'Test template']);
    $context = $this->builder->makeContext(CompileContext::MODE_TEMPLATE);
    $html = MailParser::parse(File::get(View::make('_mail.test_template')->getPath()))['html'];
    $chunks = MailImporter::splitChunks($html);

    $legacy = fn(string $view, int $segment, string $hash): array => [
        ['id' => 'b1', 'code' => 'legacy', 'settings' => ['view' => $view, 'segment' => $segment, 'hash' => $hash]],
    ];

    expect($this->builder->compileHtml($legacy('_mail.test_template', 2, sha1($chunks[2])), $context))
        ->toBe("\n".$chunks[2]."\n")
        ->and($this->builder->compileText($legacy('_mail.test_template', 2, sha1($chunks[2])), $context))->toBe($chunks[2])
        // Markup in legacy Markdown is left out of the plain-text version
        ->and($this->builder->compileText($legacy('_mail.test_template', 1, sha1($chunks[1])), $context))->toBe('HTML CONTENT')
        // Changed file content or unregistered views compile to nothing
        ->and($this->builder->compileHtml($legacy('_mail.test_template', 2, sha1('changed')), $context))->toBe('')
        ->and($this->builder->compileHtml($legacy('_mail.test_template', 9, sha1($chunks[2])), $context))->toBe('')
        ->and($this->builder->compileHtml($legacy('igniter.admin::_partials.flash', 0, ''), $context))->toBe('');

    $preview = $this->builder->makeContext(CompileContext::MODE_TEMPLATE, preview: true);
    expect($this->builder->compileHtml($legacy('_mail.test_template', 2, sha1($chunks[2])), $preview))
        ->toBe("<div data-mb-block=\"b1\">\n\n".$chunks[2]."\n\n</div>");
});

it('leaves legacy content out of plain-text layouts', function() {
    $context = $this->builder->makeContext(CompileContext::MODE_LAYOUT);

    expect($this->builder->compileText([
        ['id' => 'b1', 'code' => 'legacy', 'settings' => ['view' => 'igniter.system::_mail.layouts.default', 'segment' => 0, 'hash' => '']],
    ], $context))->toBe('');
});

it('imports line breaks and placeholders', function() {
    resolve(MailManager::class)->registerMailTemplates(['_mail.reset_test' => [
        'label' => 'Reset test',
        'variables' => ['igniter::system.mail_variables.text_group_reset'],
    ]]);
    $context = $this->builder->makeContext(CompileContext::MODE_TEMPLATE, '_mail.reset_test');

    $result = $this->importer->import("Copy the link below: <br>\n{reset_link} {unknown}\n\n<br>Sent from {site_name}.", $context);

    expect(array_column($result['blocks'], 'code'))->toBe(['text', 'text'])
        ->and($result['blocks'][0]['settings']['content'])->toBe("Copy the link below:  \n{{ \$reset_link }} {unknown}")
        ->and($result['blocks'][1]['settings']['content'])->toBe('Sent from {{ $site_name }}.');
});

it('imports conditions as block conditions', function() {
    resolve(MailManager::class)->registerMailTemplates(['_mail.condition_test' => [
        'label' => 'Condition test',
        'variables' => ['igniter::system.mail_variables.text_group_reset'],
    ]]);
    $context = $this->builder->makeContext(CompileContext::MODE_TEMPLATE, '_mail.condition_test');

    $result = $this->importer->import(implode("\n\n", [
        "@if(!empty(\$reset_link))\n    Reset here.\n\n    @partial('button', ['url' => \$reset_link])\n    Reset\n    @endpartial\n@else\nNo link.\n@endif",
        "@isset(\$reset_code)\nCode: {{ \$reset_code }}\n@endisset",
        "@unless(\$first_name)\nHi there\n@endunless",
        "@if(\$reset_code)\nYes\n@endif",
        "@if(!\$reset_code)\nNo\n@endif",
        "@if(empty(\$reset_code))\nEmpty\n@endif",
        "@if(!isset(\$reset_code))\nNot set\n@endif",
    ]), $context);

    $conditions = array_map(fn(array $block): array => [
        $block['code'], $block['settings']['visible_when'], $block['settings']['visible_mode'],
    ], $result['blocks']);

    expect($conditions)->toBe([
        ['text', 'reset_link', 'set'],
        ['button', 'reset_link', 'set'],
        ['text', 'reset_link', 'empty'],
        ['text', 'reset_code', 'set'],
        ['text', 'first_name', 'empty'],
        ['text', 'reset_code', 'set'],
        ['text', 'reset_code', 'empty'],
        ['text', 'reset_code', 'empty'],
        ['text', 'reset_code', 'empty'],
    ])->and($result['notices'])->toBe([]);
});

it('falls back for conditions it cannot express', function() {
    $context = $this->builder->makeContext(CompileContext::MODE_TEMPLATE);

    $result = $this->importer->import(implode("\n\n", [
        "@if(\$count > 0)\nMany\n@endif",
        "@if(!empty(\$not_allowed))\nHidden\n@endif",
        "@if(!empty(\$site_name))\n@if(!empty(\$site_name))\nNested\n@endif\n@endif",
        "@if(!empty(\$site_name))\n{{ phpinfo() }}\n@endif",
    ]), $context, '_mail.test_template');

    expect(array_column($result['blocks'], 'code'))->toBe(['legacy', 'legacy', 'legacy', 'legacy']);
});

it('lets extension blocks import their own markup', function() {
    resolve(MailBlockManager::class)->registerBlock(new class extends BaseMailBlock
    {
        public function blockDetails(): array
        {
            return ['code' => 'test.extension::marker', 'modes' => [CompileContext::MODE_TEMPLATE]];
        }

        public function importChunk(string $chunk, CompileContext $context): ?array
        {
            return $chunk === '@marker' ? ['found' => true] : null;
        }

        public function toBlade(array $settings, BladeWriter $writer, CompileContext $context): BladeFragment
        {
            return $writer->none();
        }
    });

    $result = $this->importer->import("@marker\n\nText", $this->builder->makeContext(CompileContext::MODE_TEMPLATE));

    expect(array_column($result['blocks'], 'code'))->toBe(['test.extension::marker', 'text']);
});
