<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\Template\Concerns;

use Igniter\Flame\Pagic\Parsers\FileParser;
use Igniter\Flame\Support\Facades\File;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Template\Page;
use Igniter\System\Classes\BaseComponent;
use Igniter\System\Classes\ComponentManager;

it('returns component by name if it exists', function() {
    $page = Page::load(resolve(ThemeManager::class)->getActiveThemeCode(), 'components');
    $component = new class extends BaseComponent
    {
        public function onRun(): string
        {
            return 'onRun';
        }
    };
    $page->loadedComponents['testComponent'] = $component;

    expect($page->getComponent('testComponent'))->toBeInstanceOf(BaseComponent::class)
        ->and($page->getComponent('nonexistentComponent'))->toBeNull();
});

it('checks if component exists', function() {
    $page = Page::load(resolve(ThemeManager::class)->getActiveThemeCode(), 'components');

    expect($page->hasComponent('testComponent'))->toBeTrue()
        ->and($page->hasComponent('nonexistentComponent'))->toBeFalse();
});

it('updates component properties and saves', function() {
    $oldContent = File::get(testThemePath().'/_pages/components.blade.php');
    $page = Page::load(resolve(ThemeManager::class)->getActiveThemeCode(), 'components');

    $page->updateComponent('testComponent', ['property' => 'new value']);

    expect($page->settings['components']['testComponent'])->toBe(['property' => 'new value']);
    File::put(testThemePath().'/_pages/components.blade.php', $oldContent);
});

it('updates different component properties and saves', function() {
    $oldContent = File::get(testThemePath().'/_pages/components.blade.php');
    $page = Page::load(resolve(ThemeManager::class)->getActiveThemeCode(), 'components');

    $page->updateComponent('testComponent', ['alias' => 'testComponentCopy', 'property' => 'new value']);

    expect($page->settings['components']['testComponentCopy'])->toBe(['property' => 'new value']);
    File::put(testThemePath().'/_pages/components.blade.php', $oldContent);
});

it('sorts components by given priorities', function() {
    $page = Page::load(resolve(ThemeManager::class)->getActiveThemeCode(), 'nested-page');

    $page->sortComponents(['testComponent testComponentCopy', 'testComponent']);

    expect(array_keys($page->settings['components']))->toBe(['testComponent testComponentCopy', 'testComponent']);
});

it('returns all components', function() {
    $page = Page::load(resolve(ThemeManager::class)->getActiveThemeCode(), 'components');

    expect($page->getComponents())->toBe(['testComponent' => [], 'test::livewire-component' => []]);
});

it('returns response using component.beforeRun event on run components', function() {
    $page = Page::load(resolve(ThemeManager::class)->getActiveThemeCode(), 'components');
    $pageCode = FileParser::on($page)->source($page, null, controller());
    $component = resolve(ComponentManager::class)->makeComponent('testComponent', $pageCode);
    $page->loadedComponents['testComponent'] = $component;
    $component->bindEvent('component.beforeRun', fn(): string => 'beforeRun');

    expect($page->runComponents())->toBe('beforeRun');
});

it('returns response using onRun method during run component lifecycle', function() {
    $page = Page::load(resolve(ThemeManager::class)->getActiveThemeCode(), 'components');
    $component = new class extends BaseComponent
    {
        public function onRun(): string
        {
            return 'onRun';
        }
    };
    $page->loadedComponents['testComponent'] = $component;

    expect($page->runComponents())->toBe('onRun');
});

it('returns response using component.run event on run components', function() {
    $page = Page::load(resolve(ThemeManager::class)->getActiveThemeCode(), 'components');
    $pageCode = FileParser::on($page)->source($page, null, controller());
    $component = resolve(ComponentManager::class)->makeComponent('testComponent', $pageCode);
    $page->loadedComponents['testComponent'] = $component;
    $component->bindEvent('component.run', fn(): string => 'ran');

    expect($page->runComponents())->toBe('ran');
});

it('sets configurable component properties', function() {
    $page = Page::load(resolve(ThemeManager::class)->getActiveThemeCode(), 'components');
    $page->setConfigurableComponentProperties('testComponent', ['property' => 'value']);

    expect($page->loadedConfigurableComponents['testComponent'])->toBe(['property' => 'value']);
});

it('sets multiple configurable component properties', function() {
    $page = Page::load(resolve(ThemeManager::class)->getActiveThemeCode(), 'components');
    $page->setConfigurableComponentProperties([
        'testComponent' => ['property' => 'value'],
        'testComponent testComponentCopy' => ['property' => 'value'],
    ]);

    expect($page->loadedConfigurableComponents['testComponent'])->toBe(['property' => 'value'])
        ->and($page->loadedConfigurableComponents['testComponent testComponentCopy'])->toBe(['property' => 'value']);
});

it('merges configurable component properties', function() {
    $page = Page::load(resolve(ThemeManager::class)->getActiveThemeCode(), 'components');

    $page->mergeConfigurableComponentProperties([
        'testComponent' => ['new' => 'value'],
        'testComponent testComponentCopy' => ['new' => 'value'],
    ]);
    expect($page->loadedConfigurableComponents['testComponent'])->toBe(['new' => 'value']);
});
