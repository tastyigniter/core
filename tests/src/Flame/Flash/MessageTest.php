<?php

namespace Igniter\Tests\Flame\Flash;

use Igniter\Flame\Flash\Message;

it('creates a message with default attributes', function() {
    $message = new Message();
    expect($message->title)->toBeNull()
        ->and($message->message)->toBeNull()
        ->and($message->level)->toBe('info')
        ->and($message->important)->toBeFalse()
        ->and($message->overlay)->toBeFalse();
});

it('updates message attributes', function() {
    $message = new Message();
    $message->update(['title' => 'New Title', 'message' => 'New Message', 'level' => 'success']);
    expect($message->title)->toBe('New Title')
        ->and($message->message)->toBe('New Message')
        ->and($message->level)->toBe('success');
});

it('converts message to array', function() {
    $message = new Message(['title' => 'Title', 'message' => 'Message', 'level' => 'info']);
    expect($message->toArray())->toBe([
        'title' => 'Title',
        'message' => 'Message',
        'level' => 'info',
        'important' => false,
        'overlay' => false,
    ]);
});

it('checks if offset exists, get, set and unset', function() {
    $message = new Message(['title' => 'Title']);
    expect($message->offsetExists('title'))->toBe(true)
        ->and($message->offsetExists('non_existent'))->toBe(false)
        ->and($message->offsetGet('title'))->toBe('Title');

    $message->offsetSet('message', 'New Title');
    expect($message->message)->toBe('New Title');

    $message->offsetUnset('title');
    expect($message->title)->toBeNull();
});
