<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Helpers;

use Igniter\System\Helpers\MailHelper;
use Illuminate\Support\Facades\Mail;

it('sends template email successfully', function() {
    Mail::shouldReceive('send')->once()->andReturn(true);
    $result = (new MailHelper)->sendTemplate('view', ['key' => 'value']);

    expect($result)->toBeTrue();
});

it('queues template email successfully', function() {
    Mail::shouldReceive('queue')->once()->andReturn(true);
    $result = (new MailHelper)->queueTemplate('view', ['key' => 'value']);

    expect($result)->toBeTrue();
});

it('sends template email with callback', function() {
    Mail::shouldReceive('send')->once()->andReturn(true);
    $callback = function($message) {
        $message->subject('Test Subject');
    };
    $result = (new MailHelper)->sendTemplate('view', ['key' => 'value'], $callback);

    expect($result)->toBeTrue();
});

it('queues template email with callback', function() {
    Mail::shouldReceive('queue')->once()->andReturn(true);
    $callback = function($message) {
        $message->subject('Test Subject');
    };
    $result = (new MailHelper)->queueTemplate('view', ['key' => 'value'], $callback);

    expect($result)->toBeTrue();
});
