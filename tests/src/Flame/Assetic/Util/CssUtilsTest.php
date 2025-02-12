<?php

namespace Igniter\Tests\Flame\Assetic\Util;

use Igniter\Flame\Assetic\Util\CssUtils;

it('filters URLs in CSS content', function() {
    $content = 'body { background: url("image.png"); }';
    $callback = fn($matches) => 'url("new_image.png")';

    $result = CssUtils::filterUrls($content, $callback);

    expect($result)->toBe('body { background: url("new_image.png"); }');
});

it('filters imports in CSS content', function() {
    $content = '@import "style.css";';
    $callback = fn($matches) => '@import "new_style.css";';

    $result = CssUtils::filterImports($content, $callback);

    expect($result)->toBe('@import "new_style.css";');
});

it('filters IE filters in CSS content', function() {
    $content = 'filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src="image.png");';
    $expected = 'filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(http://example.com/image.png);';
    $result = CssUtils::filterIEFilters($content, fn($matches) => 'http://example.com/image.png');

    expect($result)->toBe($expected);
});

it('filters references in CSS content', function() {
    $content = 'body { background: url("image.png"); } @import "style.css"; filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src="image.png");';
    $callback = fn($matches) => str_replace('image.png', 'new_image.png', $matches[0]);

    $result = CssUtils::filterReferences($content, $callback);

    expect($result)->toBe('body { background: url("new_image.png"); } @import "style.css"; filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src="new_image.png");');
});

it('extracts imports from CSS content', function() {
    $content = '@import "style1.css"; @import "style2.css";';

    expect(CssUtils::extractImports($content))->toBe(['style1.css', 'style2.css']);
});

it('filters URLs in CSS content with comments', function() {
    $content = '/* comment */ body { background: url("image.png"); }';
    $callback = fn($matches) => 'url("new_image.png")';


    expect(CssUtils::filterUrls($content, $callback))->toBe('/* comment */ body { background: url("new_image.png"); }');
});

it('filters imports in CSS content with comments', function() {
    $content = '/* comment */ @import "style.css";';
    $callback = fn($matches) => '@import "new_style.css";';

    expect(CssUtils::filterImports($content, $callback))->toBe('/* comment */ @import "new_style.css";');
});

it('filters IE filters in CSS content with comments', function() {
    $content = '/* comment */ filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src="image.png");';
    $expected = '/* comment */ filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(http://example.com/new_image.png);';

    expect(CssUtils::filterIEFilters($content, fn($matches) => 'http://example.com/new_image.png'))->toBe($expected);
});
