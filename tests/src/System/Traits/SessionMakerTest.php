<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Traits;

use Igniter\System\Traits\SessionMaker;

it('retrieves value from session with key', function() {
    session()->put('class_id.key', 'value');
    $sessionMaker = new class
    {
        use SessionMaker;

        protected $sessionKey = 'class_id';
    };

    $result = $sessionMaker->getSession('key');
    expect($result)->toBe('value');
});

it('retrieves default value when key not in session', function() {
    $sessionMaker = new class
    {
        use SessionMaker;
    };

    $result = $sessionMaker->getSession('nonexistent_key', 'default_value');
    expect($result)->toBe('default_value');
});

it('saves key value pair in session', function() {
    $sessionMaker = new class
    {
        use SessionMaker;

        protected $sessionKey = 'class_id';
    };

    $sessionMaker->putSession('key', 'value');
    expect(session()->get('class_id.key'))->toBe('value');
});

it('checks if session has key', function() {
    session()->put('class_id.key', 'value');
    $sessionMaker = new class
    {
        use SessionMaker;

        protected $sessionKey = 'class_id';
    };

    $result = $sessionMaker->hasSession('key');
    expect($result)->toBeTrue();
});

it('flashes key value pair in session', function() {
    $sessionMaker = new class
    {
        use SessionMaker;

        protected $sessionKey = 'class_id';
    };

    $sessionMaker->flashSession('key', 'value');
    expect(session()->get('class_id.key'))->toBe('value');
});

it('forgets key from session', function() {
    session()->put('class_id.key', 'value');
    $sessionMaker = new class
    {
        use SessionMaker;

        protected $sessionKey = 'class_id';
    };

    $sessionMaker->forgetSession('key');
    expect(session()->has('class_id.key'))->toBeFalse();
});

it('resets session', function() {
    session()->put('class_id.key1', 'value1');
    session()->put('class_id.key2', 'value2');
    $sessionMaker = new class
    {
        use SessionMaker;

        protected $sessionKey = 'class_id';
    };

    $sessionMaker->resetSession();
    expect(session()->has('class_id.key1'))->toBeFalse()
        ->and(session()->has('class_id.key2'))->toBeFalse();
});

it('sets custom session key', function() {
    $sessionMaker = new class
    {
        use SessionMaker;
    };

    $sessionMaker->setSessionKey('custom_key');
    $sessionMaker->putSession('key', 'value');

    expect(session()->get('custom_key.key'))->toBe('value');
});
