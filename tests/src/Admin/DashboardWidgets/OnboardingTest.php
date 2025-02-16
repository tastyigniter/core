<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\DashboardWidgets;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Classes\OnboardingSteps;
use Igniter\Admin\DashboardWidgets\Onboarding;
use Igniter\System\Facades\Assets;

beforeEach(function() {
    $this->controller = $this->createMock(AdminController::class);
    $this->onboardingSteps = $this->createMock(OnboardingSteps::class);
    $this->onboarding = new Onboarding($this->controller);
});

it('tests initialize', function() {
    expect($this->onboarding->property('cssClass'))->toBe('widget-item-onboarding');
});

it('tests loadAssets', function() {
    Assets::shouldReceive('addCss')->once()->with('onboarding.css', 'onboarding-css');

    $this->onboarding->assetPath = [];

    // Call the loadAssets method
    $this->onboarding->loadAssets();
});

it('tests render', function() {
    $this->instance(OnboardingSteps::class, $this->onboardingSteps);

    $this->onboarding->render();

    expect($this->onboarding->vars['onboarding'])->toBe($this->onboardingSteps);
});
