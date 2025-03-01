<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Traits;

use Igniter\Flame\Traits\EventEmitter;

it('unbinds single event', function() {
    $emitter = new class
    {
        use EventEmitter;
    };
    $emitter->bindEventOnce('test.event', function() {});
    $emitter->unbindEvent('test.event');

    expect($emitter->fireEvent('test.event'))->toBe([]);
});

it('unbinds multiple events', function() {
    $emitter = new class
    {
        use EventEmitter;
    };
    $emitter->bindEvent('test.event1', function() {});
    $emitter->bindEvent('test.event2', function() {});
    $emitter->bindEvent('test.event3', function() {});
    $emitter->unbindEvent(['test.event1', 'test.event2']);

    expect($emitter->fireEvent('test.event1'))->toBe([])
        ->and($emitter->fireEvent('test.event2'))->toBe([])
        ->and($emitter->fireEvent('test.event3'))->toBe([]);

    $emitter->unbindEvent('test.event3');
});

it('unbinds all events when no event is specified', function() {
    $emitter = new class
    {
        use EventEmitter;
    };
    $emitter->bindEvent('test.event1', function() {});
    $emitter->bindEvent('test.event2', function() {});
    $emitter->unbindEvent();
    expect($emitter->fireEvent('test.event1'))->toBe([])
        ->and($emitter->fireEvent('test.event2'))->toBe([]);
});

it('fires single event and returns results', function() {
    $emitter = new class
    {
        use EventEmitter;
    };
    $emitter->bindEventOnce('test.event', fn(string $param): string => $param.'1');
    $emitter->bindEventOnce('test.event', fn(string $param): string => $param.'2');
    expect($emitter->fireEvent('test.event', ['value']))->toBe(['value1', 'value2'])
        ->and($emitter->fireEvent('test.event', ['value']))->toBe([]);
});

it('fires single event and halts on first non-null result', function() {
    $emitter = new class
    {
        use EventEmitter;
    };
    $emitter->bindEventOnce('test.event', fn($param): null => null);
    $emitter->bindEventOnce('test.event', fn($param) => $param);
    expect($emitter->fireEvent('test.event', ['value'], true))->toBe('value')
        ->and($emitter->fireEvent('test.event', ['value'], true))->toBeNull();
});

it('fires multiple events and returns all results', function() {
    $emitter = new class
    {
        use EventEmitter;
    };
    $emitter->bindEvent('test.event', fn(string $param): string => $param.'1');
    $emitter->bindEvent('test.event', fn(string $param): string => $param.'2');

    expect($emitter->fireEvent('test.event', ['value']))->toBe(['value1', 'value2']);
});

it('fires event with no listeners and returns empty array', function() {
    $emitter = new class
    {
        use EventEmitter;
    };
    expect($emitter->fireEvent('test.event'))->toBe([]);
});
