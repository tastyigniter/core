<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\Classes;

use Igniter\Flame\Exception\SystemException;
use Igniter\Main\Classes\ComponentBlock;
use Igniter\System\Classes\ComponentManager;
use Igniter\Tests\Main\Fixtures\TestBlockBladeComponent;
use Igniter\Tests\Main\Fixtures\TestBlockLivewireComponent;

beforeEach(function() {
    $manager = resolve(ComponentManager::class);
    $manager->registerComponent(TestBlockLivewireComponent::class, TestBlockLivewireComponent::componentMeta());
    $manager->registerComponent(TestBlockBladeComponent::class, TestBlockBladeComponent::componentMeta());
});

it('builds block details from the component meta', function() {
    $block = ComponentBlock::make('test::block-livewire', ['unique' => true, 'wide' => true]);

    expect($block->getComponentCode())->toBe('test::block-livewire')
        ->and($block->getCode())->toBe('block-livewire')
        ->and($block->getName())->toBe('Block Livewire Component')
        ->and($block->getDetails()['description'])->toBe('Livewire component used as a page builder block')
        ->and($block->isUnique())->toBeTrue()
        ->and($block->isWide())->toBeTrue();
});

it('lets details override the component meta', function() {
    $block = ComponentBlock::make('test::block-blade', ['code' => 'featured', 'name' => 'Featured items']);

    expect($block->getCode())->toBe('featured')
        ->and($block->getName())->toBe('Featured items');
});

it('falls back to the component code when the component is not registered', function() {
    $block = ComponentBlock::make('test::missing');

    expect($block->getName())->toBe('test::missing')
        ->and($block->getDetails()['description'])->toBeNull();
});

it('builds fields from the component properties', function() {
    $block = ComponentBlock::make('test::block-blade');

    $fields = $block->defineFields();

    expect(array_keys($fields))->toBe(['title', 'itemsPerRow'])
        ->and($fields['title'])->toMatchArray(['label' => 'Title', 'type' => 'text'])
        ->and($fields['itemsPerRow'])->toMatchArray(['label' => 'Items per row', 'type' => 'number'])
        ->and($block->defineFields())->toBe($fields);
});

it('returns defaults from the component property values', function() {
    $block = ComponentBlock::make('test::block-livewire');

    expect($block->defaults())->toBe(['title' => 'Default title'])
        ->and($block->defaults())->toBe(['title' => 'Default title'])
        ->and($block->sanitize(['title' => 'Hello', 'unknown' => 'value']))->toBe(['title' => 'Hello']);
});

it('renders a livewire component with the block settings', function() {
    $html = ComponentBlock::make('test::block-livewire')->render('tests-theme', [
        'id' => 'b_hero',
        'code' => 'block-livewire',
        'settings' => ['title' => 'From block'],
    ]);

    expect($html)->toContain('block-livewire')->toContain('From block');
});

it('renders a livewire component without a block id', function() {
    $html = ComponentBlock::make('test::block-livewire')->render('tests-theme', ['settings' => []]);

    expect($html)->toContain('block-livewire');
});

it('renders a blade component with the block settings', function() {
    $html = ComponentBlock::make('test::block-blade')->render('tests-theme', [
        'id' => 'b_featured',
        'code' => 'block-blade',
        'settings' => ['title' => 'Featured', 'itemsPerRow' => 4, 'invalid key' => 'ignored'],
    ]);

    expect($html)->toContain('<div class="block-blade">Featured-4</div>');
});

it('throws an exception when rendering a non configurable component', function() {
    expect(fn() => ComponentBlock::make('testComponent')->render('tests-theme', ['settings' => []]))
        ->toThrow(SystemException::class, 'is not a Livewire or Blade component');
});
