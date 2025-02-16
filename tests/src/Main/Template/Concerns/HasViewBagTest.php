<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\Template\Concerns;

use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Components\ViewBag;
use Igniter\Main\Template\Concerns\HasViewBag;
use Igniter\Main\Template\Page;

it('initializes view bag cache on first access', function() {
    $page = Page::load(resolve(ThemeManager::class)->getActiveThemeCode(), 'components');

    expect($page->getViewBag())->toBe($page->getViewBag());
});

it('fills view bag array with properties from view bag', function() {
    $traitObject = new class extends Page
    {
        use HasViewBag;

        public function testFillViewBagArray()
        {
            $this->fillViewBagArray();
        }
    };

    $traitObject->settings = ['components' => ['viewBag' => []]];
    $component = new ViewBag(null, ['property1' => 'value1', 'property2' => 'value2']);
    $traitObject->loadedComponents['viewBag'] = $component;

    $traitObject->testFillViewBagArray();
    expect($traitObject->viewBag)->toBe(['property1' => 'value1', 'property2' => 'value2']);
});
