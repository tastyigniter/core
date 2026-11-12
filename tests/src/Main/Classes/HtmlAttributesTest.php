<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\Classes;

use Igniter\Main\Classes\HtmlAttributes;

it('parses one attribute per line', function() {
    $attributes = HtmlAttributes::parse(implode("\n", [
        'data-track="hero"',
        "aria-label='Opening hours'",
        'role=banner',
        'hidden',
        '',
        'not an attribute line ="',
    ]));

    expect($attributes)->toBe([
        'data-track' => 'hero',
        'aria-label' => 'Opening hours',
        'role' => 'banner',
        'hidden' => '',
    ]);
});

it('drops unsafe attributes and values', function() {
    expect(HtmlAttributes::parse(implode("\n", [
        'onclick="alert(1)"',
        'href="https://example.com"',
        'data-url="javascript:alert(1)"',
        'style="width: expression(alert(1))"',
        'title="'.str_repeat('a', 501).'"',
        'style="color: red"',
    ])))->toBe(['style' => 'color: red'])
        ->and(HtmlAttributes::parse(null))->toBe([])
        ->and(HtmlAttributes::parse('   '))->toBe([]);
});

it('builds block attributes with the css class', function() {
    expect(HtmlAttributes::forBlock(['css_class' => ' promo  wide"> x ', 'html_attributes' => 'data-a=1']))
        ->toBe(['data-a' => '1', 'class' => 'promo wide x'])
        ->and(HtmlAttributes::forBlock([]))->toBe([]);
});

it('renders escaped attributes', function() {
    expect(HtmlAttributes::toHtml(['data-a' => 'x"y', 'hidden' => '', 'class' => 'a b']))
        ->toBe('data-a="x&quot;y" hidden class="a b"');
});
