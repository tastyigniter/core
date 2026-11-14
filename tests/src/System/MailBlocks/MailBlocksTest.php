<?php

declare(strict_types=1);

namespace Igniter\Tests\System\MailBlocks;

use Igniter\System\Classes\MailBuilder\BaseMailBlock;
use Igniter\System\Classes\MailBuilder\BladeWriter;
use Igniter\System\Classes\MailBuilder\CompileContext;
use Igniter\System\Classes\MailBuilder\MailBlockManager;
use Igniter\System\Classes\MailBuilder\MailBuilder;
use Igniter\System\Classes\MailBuilder\MailVariables;
use Igniter\System\Classes\MailBuilder\TextWriter;
use Igniter\System\MailBlocks\Legacy;

beforeEach(function() {
    $this->builder = resolve(MailBuilder::class);
    $this->compile = function(string $mode, string $code, array $settings = [], ?CompileContext $context = null): array {
        $context ??= $this->builder->makeContext($mode);
        $block = resolve(MailBlockManager::class)->findBlock($code);
        $settings = $block->sanitize($settings);

        return [
            $block->toBlade($settings, new BladeWriter($context), $context)->toBlade(),
            $block->toText($settings, new TextWriter($context), $context)->toBlade(),
            $block->summary($settings),
        ];
    };
});

it('compiles headings', function() {
    [$html, $text, $summary] = ($this->compile)('template', 'heading', ['text' => 'Hi', 'level' => 'h3', 'align' => 'center']);

    expect($html)->toBe('<h3 style="text-align: center">Hi</h3>')
        ->and($text)->toBe('Hi')
        ->and($summary)->toBe('Hi')
        ->and(($this->compile)('template', 'heading', ['text' => 'Hi', 'level' => 'h9', 'align' => 'x'])[0])
        ->toBe('<h1 style="text-align: left">Hi</h1>');
});

it('compiles text', function() {
    [$html, $text] = ($this->compile)('template', 'text', ['content' => '**Hi** {{ $site_name }}']);

    expect($html)->toBe('<p><strong>Hi</strong> {{ $site_name }}</p>')
        ->and($text)->toBe('**Hi** {{ $site_name }}')
        ->and(($this->compile)('template', 'text', ['content' => 'Hi', 'align' => 'center'])[0])
        ->toBe('<div style="text-align: center"><p>Hi</p></div>')
        ->and(($this->compile)('template', 'text', ['content' => 'Hi', 'align' => 'bad'])[0])->toBe('<p>Hi</p>')
        ->and(($this->compile)('template', 'text', ['content' => '', 'align' => 'right'])[0])->toBe('');
});

it('compiles buttons', function() {
    [$html, $text] = ($this->compile)('template', 'button', ['label' => 'Go', 'url' => 'https://example.com', 'style' => 'success']);

    expect($html)->toBe(sprintf(" @partial('button', ['url' => base64_decode('%s'), 'type' => base64_decode('%s'), 'align' => base64_decode('%s')])Go @endpartial",
        base64_encode('https://example.com'), base64_encode('success'), base64_encode('center')))
        ->and(($this->compile)('template', 'button', ['label' => 'Go', 'url' => 'https://example.com', 'align' => 'right'])[0])
        ->toContain(base64_encode('right'))
        ->and(($this->compile)('template', 'button', ['label' => 'Go', 'url' => 'https://example.com', 'align' => 'bad'])[0])
        ->toContain(base64_encode('center'))
        ->and($text)->toBe('Go: https://example.com')
        ->and(($this->compile)('template', 'button', ['label' => 'Go', 'url' => 'https://example.com', 'style' => 'bad'])[0])
        ->toContain(base64_encode('primary'))
        ->and(($this->compile)('template', 'button', ['label' => 'Go', 'url' => 'javascript:x']))->toBe(['', '', 'Go']);
});

it('compiles images', function() {
    [$html, $text, $summary] = ($this->compile)('template', 'image', [
        'src' => 'https://example.com/a.png', 'alt' => 'Logo', 'width' => 200, 'align' => 'right', 'link' => 'https://example.com',
    ]);

    expect($html)->toBe('<p style="text-align: right"><a href="https://example.com" target="_blank">'
        .'<img src="https://example.com/a.png" alt="Logo" width="200" style="max-width: 100%; height: auto; width: 200px"></a></p>')
        ->and($text)->toBe('[Logo]')
        ->and($summary)->toBe('Logo')
        ->and(($this->compile)('template', 'image', ['src' => 'https://example.com/a.png'])[1])->toBe('')
        ->and(($this->compile)('template', 'image', ['src' => 'https://example.com/a.png'])[2])->toBe('a.png')
        ->and(($this->compile)('template', 'image', ['src' => 'javascript:x'])[0])->toBe('');
});

it('compiles dividers, spacers and columns', function() {
    expect(($this->compile)('template', 'divider'))->toBe(['<hr style="border: 0; border-top: 1px solid #edeff2; margin: 24px 0;">', '----------', ''])
        ->and(($this->compile)('template', 'spacer', ['height' => 500]))
        ->toBe(['<div style="height: 200px; line-height: 200px;">&nbsp;</div>', '', '500px'])
        ->and(($this->compile)('template', 'columns', ['left' => 'A', 'right' => 'B'])[0])
        ->toBe('<table width="100%" cellpadding="0" cellspacing="0"><tr><td width="50%" valign="top" style="padding: 0 8px;"><p>A</p></td>'
            .'<td width="50%" valign="top" style="padding: 0 8px;"><p>B</p></td></tr></table>')
        ->and(($this->compile)('template', 'columns', ['left' => 'A', 'right' => 'B'])[1])->toBe("A\n\nB");
});

it('compiles panels, promotions and subcopy', function(string $code) {
    [$html, $text, $summary] = ($this->compile)('template', $code, ['content' => '**Note** {{ $site_name }} {{ phpinfo() }}']);

    expect($html)->toBe(" @partial('".$code."')<p><strong>Note</strong> {{ \$site_name }} &#123;&#123; phpinfo() &#125;&#125;</p> @endpartial")
        ->and($text)->toBe(" @partial('".$code."')**Note** {{ \$site_name }} &#123;&#123; phpinfo() &#125;&#125; @endpartial")
        ->and($summary)->toBe('**Note** {{ $site_name }} {{ phpinfo() }}')
        ->and(($this->compile)('template', $code, ['content' => ''])[0])->toBe('')
        // A partial does not include itself
        ->and(($this->compile)('partial', $code, ['content' => 'x'], $this->builder->makeContext('partial', $code))[0])->toBe('');
})->with(['panel', 'promotion', 'subcopy']);

it('compiles tables', function() {
    [$html, $text, $summary] = ($this->compile)('template', 'table', ['table' => [
        'columns' => [['label' => 'Item', 'align' => 'left'], ['label' => '<b>Price</b>', 'align' => 'right']],
        'rows' => [['Pizza', '{{ $site_name }}'], ['{{ phpinfo() }}']],
    ]]);

    expect($html)->toBe(" @partial('table')<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\"><thead><tr>"
        .'<th align="left" style="text-align: left">Item</th><th align="right" style="text-align: right">&lt;b&gt;Price&lt;/b&gt;</th></tr></thead><tbody>'
        .'<tr><td align="left" style="text-align: left">Pizza</td><td align="right" style="text-align: right">{{ $site_name }}</td></tr>'
        .'<tr><td align="left" style="text-align: left">&#123;&#123; phpinfo() &#125;&#125;</td><td align="right" style="text-align: right"></td></tr>'
        .'</tbody></table> @endpartial')
        ->and($text)->toBe(" @partial('table')Item | &lt;b&gt;Price&lt;/b&gt;\nPizza | {{ \$site_name }}\n&#123;&#123; phpinfo() &#125;&#125; | - @endpartial")
        ->and($summary)->toBe('Item, <b>Price</b>')
        ->and(($this->compile)('template', 'table', ['table' => ['columns' => []]]))->toBe(['', '', '']);
});

it('sanitizes table values', function() {
    $table = BaseMailBlock::sanitizeTable([
        'columns' => array_merge(['A', ['label' => ['x'], 'align' => 'bad']], array_fill(0, 10, 'C')),
        'rows' => [['a', ['x'], 'c', 'd'], 'b'],
    ]);

    expect($table['columns'])->toHaveCount(8)
        ->and($table['columns'][0])->toBe(['label' => 'A', 'align' => 'left'])
        ->and($table['columns'][1])->toBe(['label' => '', 'align' => 'left'])
        ->and($table['rows'][0])->toBe(['a', '', 'c', 'd', '', '', '', ''])
        ->and($table['rows'][1][0])->toBe('b')
        ->and(BaseMailBlock::sanitizeTable('x'))->toBe(['columns' => [], 'rows' => []]);
});

it('compiles layout blocks', function() {
    [$header, $headerText] = ($this->compile)('layout', 'header', ['show_logo' => true, 'text' => '{{ $site_name }}', 'url' => 'https://example.com']);

    expect($header)->toBe(sprintf(
        " @partial('header', ['url' => base64_decode('%s')]) @if(!empty(\$mail_logo_url))<img src=\"{{ \$mail_logo_url }}\" alt=\"{{ \$site_name }}\" style=\"max-height: 90px;\"> @endif @endpartial",
        base64_encode('https://example.com'),
    ))
        ->and($headerText)->toBe('{{ $site_name }}')
        ->and(($this->compile)('layout', 'header', ['show_logo' => false, 'text' => 'Site'])[0])->toBe(" @partial('header')Site @endpartial")
        ->and(($this->compile)('layout', 'footer', ['content' => 'Bye'])[0])->toBe(" @partial('footer')<p>Bye</p> @endpartial")
        ->and(($this->compile)('layout', 'footer', ['content' => 'Bye'])[1])->toBe("-------------------\nBye")
        ->and(($this->compile)('layout', 'preheader', ['text' => 'Soon'])[0])
        ->toBe('<tr><td style="display: none; max-height: 0; overflow: hidden; mso-hide: all;">Soon</td></tr>')
        ->and(($this->compile)('layout', 'content_slot')[0])->toContain('<td class="content-cell">{!! $body !!}</td>')
        ->and(($this->compile)('layout', 'content_slot')[1])->toBe('{!! $body !!}');
});

it('compiles header and footer rows without partials', function() {
    $context = new CompileContext(CompileContext::MODE_LAYOUT, new MailVariables);

    expect(($this->compile)('layout', 'header', ['text' => 'Site', 'show_logo' => true], $context)[0])
        ->toBe('<tr><td class="header">Site</td></tr>')
        ->and(($this->compile)('layout', 'footer', ['content' => 'Bye'], $context)[0])
        ->toBe('<tr><td class="footer" align="center"><p>Bye</p></td></tr>');
});

it('compiles partial blocks', function() {
    $context = $this->builder->makeContext(CompileContext::MODE_PARTIAL, 'promo', [['name' => 'promo_code']]);

    expect(($this->compile)('partial', 'slot', [], $context))->toBe(['{!! $slot !!}', '{!! $slot !!}', ''])
        ->and(($this->compile)('partial', 'parameter', ['name' => 'promo_code'], $context))
        ->toBe(['{{ $promo_code }}', '{{ $promo_code }}', 'promo_code'])
        ->and(($this->compile)('partial', 'parameter', ['name' => 'missing'], $context))->toBe(['', '', 'missing']);
});

it('describes blocks for the builder', function() {
    $block = resolve(MailBlockManager::class)->findBlock('heading');
    $config = $block->toConfig();

    expect($config)->toMatchArray([
        'code' => 'heading',
        'name' => lang('igniter::system.mail_builder.block_heading'),
        'icon' => 'fa fa-heading',
        'category' => 'content',
        'unique' => false,
        'readOnly' => false,
    ])
        ->and($config['fields'][0])->toMatchArray(['name' => 'text', 'type' => 'text', 'variables' => true])
        ->and($config['fields'][1]['options'])->toBe(['h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3'])
        ->and($block->validationRules())->toHaveKey('text')
        ->and($block->getModes())->toBe(CompileContext::MODES)
        ->and($block->isRequired('layout'))->toBeFalse()
        ->and($block->isLayoutRow())->toBeFalse()
        ->and($block->preview([]))->toBe([])
        ->and($block->assets())->toBe([]);

    $block->addFields(['extra' => ['label' => 'Extra', 'type' => 'switch']]);
    expect($block->sanitize(['extra' => 1])['extra'])->toBeTrue();
    $block->removeField('extra');
    expect($block->formFields())->not->toHaveKey('extra');

    $slot = resolve(MailBlockManager::class)->findBlock('content_slot');
    expect($slot->isRequired('layout'))->toBeTrue()
        ->and($slot->isRequired('template'))->toBeFalse()
        ->and($slot->isLayoutRow())->toBeTrue()
        ->and(resolve(MailBlockManager::class)->findBlock('divider')->toText([], new TextWriter($this->builder->makeContext('template')), $this->builder->makeContext('template'))->toBlade())
        ->toBe('----------')
        ->and(resolve(MailBlockManager::class)->findBlock('preheader')->toText([], new TextWriter($this->builder->makeContext('layout')), $this->builder->makeContext('layout'))->isEmpty())
        ->toBeTrue();
});

it('treats legacy blocks as read-only references', function() {
    $legacy = new Legacy;
    $context = $this->builder->makeContext(CompileContext::MODE_TEMPLATE);

    expect($legacy->isReadOnly())->toBeTrue()
        ->and($legacy->sanitize(['view' => 1, 'segment' => 'x', 'hash' => null, 'extra' => 'y']))
        ->toBe(['view' => '', 'segment' => -1, 'hash' => ''])
        ->and($legacy->toBlade([], new BladeWriter($context), $context)->isEmpty())->toBeTrue()
        ->and($legacy->summary([]))->toBe(lang('igniter::system.mail_builder.text_legacy_summary'))
        ->and($legacy->isRegisteredView('igniter.system::_mail.layouts.default', 'layout'))->toBeTrue()
        ->and($legacy->isRegisteredView('igniter.system::_mail.partials.button', 'partial'))->toBeTrue()
        ->and($legacy->isRegisteredView('x', 'unknown'))->toBeFalse();
});
