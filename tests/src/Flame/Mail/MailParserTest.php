<?php

namespace Igniter\Tests\Flame\Mail;

use Igniter\Flame\Mail\MailParser;

it('parses content with three sections correctly', function() {
    $content = "setting1=value1\nsetting2=value2\n==\nPlain text content\n==\nHTML content";
    $result = MailParser::parse($content);
    expect($result['settings'])->toBe(['setting1' => 'value1', 'setting2' => 'value2'])
        ->and($result['text'])->toBe('Plain text content')
        ->and($result['html'])->toBe('HTML content');
});

it('parses content with two sections correctly', function() {
    $content = "setting1=value1\nsetting2=value2\n==\nHTML content";
    $result = MailParser::parse($content);
    expect($result['settings'])->toBe(['setting1' => 'value1', 'setting2' => 'value2'])
        ->and($result['html'])->toBe('HTML content')
        ->and($result['text'])->toBeNull();
});

it('parses content with one section correctly', function() {
    $content = 'HTML content';
    $result = MailParser::parse($content);
    expect($result['settings'])->toBe([])
        ->and($result['html'])->toBe('HTML content')
        ->and($result['text'])->toBeNull();
});

it('returns empty settings and null html and text for empty content', function() {
    $content = '';
    $result = MailParser::parse($content);
    expect($result['settings'])->toBe([])
        ->and($result['html'])->toBe('')
        ->and($result['text'])->toBeNull();
});
