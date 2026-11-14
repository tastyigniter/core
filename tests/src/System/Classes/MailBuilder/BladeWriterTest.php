<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Classes\MailBuilder;

use Igniter\Flame\Exception\SystemException;
use Igniter\System\Classes\MailBuilder\BladeWriter;
use Igniter\System\Classes\MailBuilder\CompileContext;
use Igniter\System\Classes\MailBuilder\MailBuilder;
use Igniter\System\Classes\MailBuilder\MailVariables;
use Igniter\System\Classes\MailBuilder\TextWriter;

beforeEach(function() {
    $this->variables = new MailVariables([
        'Order' => [
            '{{ $order_number }}' => 'Order number',
            '{{ $site_name }}' => 'Site name',
            '$order_menus[]' => 'Menus',
            '{{ $order_menu[\'menu_name\'] }}' => 'Menu name',
            '{!! $order_menu[\'menu_options\'] !!}' => 'Menu options',
        ],
    ]);
    $this->context = new CompileContext(CompileContext::MODE_TEMPLATE, $this->variables, partials: ['button' => 'Button']);
    $this->writer = new BladeWriter($this->context);
});

it('escapes text and keeps allowed variables', function() {
    expect($this->writer->text('Order {{ $order_number }} for <b>{{ $unknown }}</b> & @php')->toBlade())
        ->toBe('Order {{ $order_number }} for &lt;b&gt;&#123;&#123; $unknown &#125;&#125;&lt;/b&gt; &amp; &#64;php')
        ->and($this->writer->attr(['array'])->toBlade())->toBe('')
        ->and($this->writer->isPlain())->toBeFalse()
        ->and($this->writer->getContext())->toBe($this->context);
});

it('neutralises blade injection attempts in text', function(string $payload) {
    $blade = $this->writer->text($payload)->toBlade();

    expect($blade)->not->toContain('{{')
        ->and($blade)->not->toContain('{!!')
        ->and($blade)->not->toContain('@')
        ->and($blade)->not->toContain('<?');
})->with([
    ['{{ phpinfo() }}'],
    ['{!! system("id") !!}'],
    ['@php echo 1; @endphp'],
    ['<?php echo 1; ?>'],
    ['@include("secret")'],
    ['{{ $order_number->delete() }}'],
    ["{{ \$order_menu['menu_name'] }}"],
]);

it('converts markdown and keeps allowed variables', function() {
    $blade = $this->writer->markdown("Hello **{{ \$order_number }}** [link](https://example.com)\n\n- {{ phpinfo() }}\n\n<script>x</script>")->toBlade();

    expect($blade)->toContain('<strong>{{ $order_number }}</strong>')
        ->and($blade)->toContain('&#123;&#123; phpinfo() &#125;&#125;')
        ->and($blade)->toContain('&lt;script&gt;')
        ->and($blade)->toContain('<a href="https://example.com">link</a>')
        ->and($blade)->not->toContain("\n")
        ->and($this->writer->markdown('  ')->isEmpty())->toBeTrue();
});

it('drops unsafe urls', function() {
    expect($this->writer->url('javascript:alert(1)')->isEmpty())->toBeTrue()
        ->and($this->writer->url('')->isEmpty())->toBeTrue()
        ->and($this->writer->url('{{ $unknown }}/path')->isEmpty())->toBeTrue()
        ->and($this->writer->url('https://example.com/?a=1&b={{ $order_number }}')->toBlade())
        ->toBe('https://example.com/?a=1&amp;b={{ $order_number }}')
        ->and($this->writer->url('{{ $site_name }}/orders')->toBlade())->toBe('{{ $site_name }}/orders')
        ->and($this->writer->url('mailto:info@example.com')->toBlade())->toBe('mailto:info&#64;example.com');
});

it('writes colours and numbers', function() {
    expect($this->writer->color('#ABCDEF')->toBlade())->toBe('#ABCDEF')
        ->and($this->writer->color('red;}', '#fff')->toBlade())->toBe('#fff')
        ->and($this->writer->color('red', 'blue')->toBlade())->toBe('')
        ->and($this->writer->number('12', 0, 10)->toBlade())->toBe('10')
        ->and($this->writer->number('abc', 0, 10, 5)->toBlade())->toBe('5')
        ->and($this->writer->number(-5, 0)->toBlade())->toBe('0');
});

it('echoes allowed variables only', function() {
    expect($this->writer->var('order_number')->toBlade())->toBe('{{ $order_number }}');

    expect(fn() => $this->writer->var('unknown'))->toThrow(SystemException::class);
});

it('writes partials with encoded parameters', function() {
    $blade = $this->writer->partial('button', [
        'url' => 'https://example.com/{{ $order_number }}',
        'type' => "it's",
        'empty' => '',
        'bad name' => 'x',
        'slot' => 'ignored',
    ], $this->writer->text('Go'))->toBlade();

    expect($blade)->toBe(sprintf(
        " @partial('button', ['url' => base64_decode('%s') . \$order_number, 'type' => base64_decode('%s'), 'empty' => ''])Go @endpartial",
        base64_encode('https://example.com/'),
        base64_encode("it's"),
    ));

    expect(fn() => $this->writer->partial('missing'))->toThrow(SystemException::class);
});

it('writes partial parameters from item fields and unknown tokens as text', function() {
    $blade = $this->writer->loop('order_menus', fn(BladeWriter $writer, string $item) => $writer->partial('button', [
        'url' => "{{ \$order_menu['menu_name'] }}{{ \$unknown }}",
    ]))->toBlade();

    expect($blade)->toContain("'url' => \$order_menu['menu_name'] . base64_decode('".base64_encode('{{ $unknown }}')."')");
});

it('loops over registered arrays', function() {
    $blade = $this->writer->loop('order_menus', fn(BladeWriter $writer, string $item) => $writer->join(
        $writer->item($item, 'menu_name'),
        $writer->item($item, 'menu_options'),
        $writer->text("{{ \$order_menu['menu_name'] }} {{ \$order_menu['menu_options'] }} {{ \$order_menu['missing'] }}"),
    ))->toBlade();

    expect($blade)->toBe(" @foreach(\$order_menus as \$order_menu){{ \$order_menu['menu_name'] }}{!! \$order_menu['menu_options'] !!}"
        ."{{ \$order_menu['menu_name'] }} {!! \$order_menu['menu_options'] !!} &#123;&#123; \$order_menu[&#039;missing&#039;] &#125;&#125; @endforeach");

    expect(fn() => $this->writer->item('order_menu', 'menu_name'))->toThrow(SystemException::class)
        ->and(fn() => $this->writer->loop('order_number', fn() => $this->writer->none()))->toThrow(SystemException::class);
});

it('writes conditional content', function() {
    expect($this->writer->ifSet('site_name', $this->writer->text('x'))->toBlade())->toBe(' @if(!empty($site_name))x @endif')
        ->and($this->writer->ifEmpty('site_name', $this->writer->text('x'))->toBlade())->toBe(' @if(empty($site_name))x @endif');
});

it('rejects blade syntax in static markup', function(string $html) {
    expect(fn() => $this->writer->static($html))->toThrow(SystemException::class);
})->with([['{{ x }}'], ['{!! x !!}'], ['@if'], ['<?php'], ['?>']]);

it('writes tags with escaped attributes', function() {
    $blade = $this->writer->tag('a', [
        'href' => $this->writer->url('https://example.com'),
        'title' => 'Say "hi" {{ $x }}',
        'skip' => null,
        'empty' => $this->writer->none(),
        'alt' => $this->writer->none(),
    ], $this->writer->text('Link'))->toBlade();

    expect($blade)->toBe('<a href="https://example.com" title="Say &quot;hi&quot; &#123;&#123; $x &#125;&#125;" alt="">Link</a>')
        ->and($this->writer->tag('br', [], void: true)->toBlade())->toBe('<br>')
        ->and($this->writer->tag('p')->toBlade())->toBe('<p></p>');

    expect(fn() => $this->writer->tag('a b'))->toThrow(SystemException::class)
        ->and(fn() => $this->writer->tag('a', ['on click' => 'x']))->toThrow(SystemException::class);
});

it('joins fragments and skips empty ones', function() {
    expect($this->writer->joinWith(', ', $this->writer->text('a'), $this->writer->none(), $this->writer->text('b'))->toBlade())->toBe('a, b')
        ->and((string)$this->writer->join($this->writer->text('a')))->toBe('a');
});

it('writes the content slot for layouts and partials only', function() {
    $layout = new BladeWriter(new CompileContext(CompileContext::MODE_LAYOUT, $this->variables));
    $partial = new BladeWriter(new CompileContext(CompileContext::MODE_PARTIAL, $this->variables));

    expect($layout->slot()->toBlade())->toBe('{!! $body !!}')
        ->and($partial->slot()->toBlade())->toBe('{!! $slot !!}');

    expect(fn() => $this->writer->slot())->toThrow(SystemException::class);
});

it('writes plain text with the text writer', function() {
    $writer = new TextWriter($this->context);

    expect($writer->isPlain())->toBeTrue()
        ->and($writer->markdown('**Hi** {{ $order_number }} {{ phpinfo() }}')->toBlade())
        ->toBe('**Hi** {{ $order_number }} &#123;&#123; phpinfo() &#125;&#125;')
        ->and($writer->lines($writer->text('a'), $writer->text('b'))->toBlade())->toBe("a\n\nb");
});

it('separates directives from text so blade recognises them', function() {
    $blade = $this->writer->partial('button', ['url' => 'https://example.com'], $this->writer->text('Login into your account'))->toBlade();

    expect($blade)->toEndWith('account @endpartial')
        ->and(resolve(MailBuilder::class)->assertSafe($blade))->toBeNull();
});
