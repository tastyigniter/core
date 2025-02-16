<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\Template;

use Igniter\Main\Template\Content;

it('initializes correctly', function() {
    expect(Content::DIR_NAME)->toBe('_content');
});

it('initializes cache item with parsed markup', function() {
    $item = ['file_name' => 'example.md', 'markup' => '# Markdown Content'];
    Content::initCacheItem($item);
    expect($item['parsedMarkup'])->toContain('<h1>Markdown Content</h1>');
});

it('parses and returns markup if parsed markup attribute does not exist', function() {
    $content = new Content(['file_name' => 'example.md', 'markup' => '# Markdown Content']);
    expect($content->parseMarkup())->toContain('<h1>Markdown Content</h1>');
});

it('parses txt file content as HTML entities', function() {
    $content = new Content(['file_name' => 'example.txt', 'markup' => '<b>Text Content</b>']);
    expect($content->parseMarkup())->toContain('&lt;b&gt;Text Content&lt;/b&gt;');
});

it('parses md file content as HTML', function() {
    $content = new Content(['file_name' => 'example.md', 'markup' => '# Markdown Content']);
    expect($content->parseMarkup())->toContain('<h1>Markdown Content</h1>');
});

it('returns raw markup for unknown file extension', function() {
    $content = new Content(['file_name' => 'example.unknown', 'markup' => 'Raw Content']);
    expect($content->parseMarkup())->toContain('Raw Content');
});
