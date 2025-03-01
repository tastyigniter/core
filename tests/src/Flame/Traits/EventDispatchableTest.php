<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Traits;

use Igniter\Flame\Traits\EventDispatchable;
use Igniter\Tests\Fixtures\Events\TestEvent;
use Illuminate\Broadcasting\PendingBroadcast;
use Illuminate\Support\Facades\Event;

it('dispatches event when namespaced event is missing', function() {
    $event = new class
    {
        use EventDispatchable;
    };
    Event::listen($event::class, fn($event): string => 'result');

    expect($event::dispatchOnce(['data']))->toBe('result');
});

it('dispatches namespaced event once', function() {
    Event::listen('test.event', fn($event): string => 'result');
    Event::listen('test.event', fn($event): string => 'another-result');

    expect(TestEvent::dispatchOnce('test.event', ['data']))->toBe('result');
});

it('dispatches namespaced event', function() {
    Event::listen('test.event', fn($data): string => 'result');
    Event::listen('test.event', fn($data): string => 'another-result');

    expect(TestEvent::dispatch(['data']))->toBe(['result', 'another-result']);
});

it('dispatches namespaced event if condition is true', function() {
    Event::listen('test.event', fn($data): string => 'result');

    expect(TestEvent::dispatchIf(true, ['data']))->toBe(['result'])
        ->and(TestEvent::dispatchIf(false))->toBeNull();
});

it('dispatches namespaced event unless condition is true', function() {
    Event::listen('test.event', fn($data): string => 'result');

    expect(TestEvent::dispatchUnless(true))->toBeNull()
        ->and(TestEvent::dispatchUnless(false, ['data']))->toBe(['result']);
});

it('broadcasts namespaced event', function() {
    $result = TestEvent::broadcast(['data']);
    expect($result)->toBeInstanceOf(PendingBroadcast::class);
});
