<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Mail;

use Igniter\Flame\Database\Model;
use Igniter\System\Mail\AnonymousTemplateMailable;

it('creates instance with template code', function() {
    $templateCode = 'test_template';
    $mailable = AnonymousTemplateMailable::create($templateCode);

    expect($mailable->getTemplateCode())->toBe($templateCode);
});

it('adds data without models', function() {
    $mailable = new AnonymousTemplateMailable;
    $data = ['key1' => 'value1', 'key2' => new class extends Model {}];
    $mailable->with($data);

    expect($mailable->viewData)->toHaveKey('key1')
        ->and($mailable->viewData)->not->toHaveKey('key2');
});

it('applies callable callback', function() {
    $mailable = new AnonymousTemplateMailable;
    $callback = function($message) {
        $message->subject('Test Subject');
    };
    $mailable->applyCallback($callback);

    expect($mailable->callbacks)->toContain($callback);
});

it('applies array callback', function() {
    $mailable = new AnonymousTemplateMailable;
    $callback = ['test@example.com'];
    $mailable->applyCallback($callback);

    expect($mailable->to[0])->toContain('test@example.com');
});

it('applies string callback', function() {
    $mailable = new AnonymousTemplateMailable;
    $callback = 'test@example.com';
    $mailable->applyCallback($callback);

    expect($mailable->to[0])->toContain('test@example.com');
});

it('does not apply null callback', function() {
    $mailable = new AnonymousTemplateMailable;
    $callback = null;
    $mailable->applyCallback($callback);

    expect($mailable->to)->toBeEmpty();
});
