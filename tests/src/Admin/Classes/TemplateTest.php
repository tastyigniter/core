<?php

namespace Igniter\Tests\Admin\Classes;

use Igniter\Admin\Classes\Template;
use Illuminate\Support\HtmlString;

beforeEach(function() {
    $this->template = new Template;
});

it('tests getBlock', function() {
    $this->template->setBlock('test', 'content');

    $block = $this->template->getBlock('test');

    expect($block)->toBeInstanceOf(HtmlString::class)
        ->and((string)$block)->toBe('content');
});

it('tests appendBlock', function() {
    $this->template->setBlock('test', 'content');

    $this->template->appendBlock('test', '-append-content');

    expect($this->template->getBlock('test')->toHtml())->toBe('content-append-content');
});

it('tests appendBlock on unset block', function() {
    $this->template->appendBlock('test', 'append-content');

    expect($this->template->getBlock('test')->toHtml())->toBe('append-content');
});

it('tests setBlock', function() {
    $this->template->setBlock('test', 'content');

    $block = $this->template->getBlock('test');

    expect((string)$block)->toBe('content');
});

it('tests getTitle', function() {
    $this->template->setTitle('Test Title');

    expect($this->template->getTitle())->toBe('Test Title');
});

it('tests getHeading', function() {
    $this->template->setHeading('Test Heading');

    expect($this->template->getHeading())->toBe('Test Heading');
});

it('tests getButtonList', function() {
    $this->template->setButton('Test Button', ['href' => '#']);

    expect($this->template->getButtonList())->toBe('<a href="#">Test Button</a>');
});

it('tests setTitle', function() {
    $this->template->setTitle('Test Title');

    expect($this->template->getTitle())->toBe('Test Title');
});

it('tests setHeading', function() {
    $this->template->setHeading('Test Heading');

    expect($this->template->getHeading())->toBe('Test Heading');
});

it('tests setHeading with subheading', function() {
    $this->template->setHeading('Test Heading:Subheading');

    expect($this->template->getHeading())->toBe('Test Heading&nbsp;<small>Subheading</small>');
});

it('tests setButton', function() {
    $this->template->setButton('Test Button', ['href' => '#']);

    expect($this->template->getButtonList())->toBe('<a href="#">Test Button</a>');
});

it('tests renderHook', function() {
    $this->template->registerHook('test', function() {
        return 'Test Hook';
    });

    $hook = $this->template->renderHook('test');

    expect($hook)->toBeInstanceOf(HtmlString::class)
        ->and((string)$hook)->toBe('Test Hook');
});

it('tests registerHook', function() {
    $this->template->registerHook('test', function() {
        return 'Test Hook';
    });

    $hook = $this->template->renderHook('test');

    expect($hook)->toBeInstanceOf(HtmlString::class)
        ->and((string)$hook)->toBe('Test Hook');
});

it('tests renderStaticCss', function() {
    expect($this->template->renderStaticCss())->toBe('');
});
