<?php

namespace Igniter\Tests\Flame\Mail;

use Igniter\Flame\Mail\Markdown;
use Igniter\Flame\Support\Facades\File;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\HtmlString;

it('parses markdown file content correctly', function() {
    $path = 'path/to/markdown/file.md';
    File::shouldReceive('get')->with($path)->andReturn('# Markdown Content');
    $result = Markdown::parseFile($path);
    expect($result)->toBeInstanceOf(HtmlString::class)
        ->and($result->toHtml())->toContain('<h1>Markdown Content</h1>');
});

it('throws exception when file does not exist', function() {
    $path = 'path/to/nonexistent/file.md';
    File::shouldReceive('get')->with($path)->andThrow(FileNotFoundException::class);
    expect(fn() => Markdown::parseFile($path))->toThrow(FileNotFoundException::class);
});
