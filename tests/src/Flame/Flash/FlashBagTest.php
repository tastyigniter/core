<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Flash;

use Igniter\Flame\Flash\FlashBag;
use Igniter\Flame\Flash\FlashStore;
use Illuminate\Session\Store;
use Mockery;

beforeEach(function() {
    $this->session = Mockery::mock(Store::class);
    $this->flashBag = new FlashBag(new FlashStore($this->session));
});

it('sets and gets session key', function() {
    $this->flashBag->setSessionKey('new_key');
    expect($this->flashBag->getSessionKey())->toBe('new_key');
});

it('returns all messages and clears them', function() {
    $this->session->shouldReceive('get')->andReturn(collect(['message1', 'message2']));
    $this->session->shouldReceive('forget');

    expect($this->flashBag->all())->toEqual(collect(['message1', 'message2']))
        ->and($this->flashBag->messages())->toBeEmpty();
});

it('flashes message of different levels', function() {
    $this->session->shouldReceive('get')->andReturn(collect());
    $this->session->shouldReceive('flash');

    $this->flashBag->set('info', 'Alert message');
    expect($this->flashBag->messages()->last())->message->toBe('Alert message')->level->toBe('info');

    $this->flashBag->alert('Alert message');
    expect($this->flashBag->messages()->last()->message)->toBe('Alert message');

    $this->flashBag->info('Info message');
    expect($this->flashBag->messages()->last()->level)->toBe('info');

    $this->flashBag->success('Success message');
    expect($this->flashBag->messages()->last()->level)->toBe('success');

    $this->flashBag->error('Error message');
    expect($this->flashBag->messages()->last()->level)->toBe('danger');

    $this->flashBag->danger('Error message');
    expect($this->flashBag->messages()->last()->level)->toBe('danger');

    $this->flashBag->warning('Warning message');
    expect($this->flashBag->messages()->last()->level)->toBe('warning');
    $this->flashBag->message(null, 'info');
    expect($this->flashBag->messages()->last()->level)->toBe('info');

    $this->flashBag->overlay(null, 'Overlay title');
    expect($this->flashBag->messages()->last()->title)->toBe('Overlay title')
        ->and($this->flashBag->messages()->last()->message)->toBe('Warning message')
        ->and($this->flashBag->messages()->last()->overlay)->toBeTrue();

    $this->flashBag->overlay('Overlay message', 'Overlay title');
    expect($this->flashBag->messages()->last()->title)->toBe('Overlay title')
        ->and($this->flashBag->messages()->last()->message)->toBe('Overlay message')
        ->and($this->flashBag->messages()->last()->overlay)->toBeTrue();
});

it('marks message as important', function() {
    $this->session->shouldReceive('get')->andReturn(collect());
    $this->session->shouldReceive('flash');

    $this->flashBag->alert('Important message')->important();
    expect($this->flashBag->messages()->last()->important)->toBeTrue();
});

it('marks message as now', function() {
    $this->session->shouldReceive('get')->andReturn(collect());
    $this->session->shouldReceive('flash');

    $this->flashBag->alert('Now message')->now();
    expect($this->flashBag->messages()->last()->now)->toBeTrue();
});

it('clears all messages', function() {
    $this->session->shouldReceive('forget');

    $this->flashBag->clear();
    expect($this->flashBag->messages())->toBeEmpty();
});
