<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\Classes;

use Igniter\Admin\Classes\OnboardingSteps;
use Igniter\System\Classes\ExtensionManager;

dataset('onboardingSteps', [
    fn() => [
        'testStep1' => [
            'label' => 'Test Step 1',
            'description' => 'This is test step 1',
            'icon' => 'fa fa-angle-double-right',
            'url' => 'http://localhost/admin/testStep1',
            'priority' => 500,
            'complete' => function() {
                return false;
            },
        ],
        'testStep2' => [
            'label' => 'Test Step 2',
            'description' => 'This is test step 2',
            'icon' => 'fa fa-angle-double-left',
            'url' => 'http://localhost/admin/testStep2',
            'priority' => 1000,
            'complete' => function() {
                return true;
            },
        ],
    ],
]);

beforeEach(function() {
    $this->onboardingSteps = new OnboardingSteps;
});

afterEach(function() {
    OnboardingSteps::clearCallbacks();
});

it('adds, gets and removes onboarding steps correctly', function() {
    $this->onboardingSteps->registerSteps([
        'testStep' => [
            'label' => 'Test Step',
            'description' => 'This is a test step',
            'icon' => 'fa fa-angle-double-right',
            'url' => 'http://localhost/admin/testStep',
            'priority' => 500,
            'complete' => fn() => true,
            'completed' => fn() => true,
        ],
    ]);

    $step = $this->onboardingSteps->getStep('testStep');

    expect($step->label)->toBe('Test Step')
        ->and($step->description)->toBe('This is a test step')
        ->and($step->icon)->toBe('fa fa-angle-double-right')
        ->and($step->url)->toBe('http://localhost/admin/testStep')
        ->and($step->priority)->toBe(500)
        ->and($step->complete)->toBeCallable();

    $this->onboardingSteps->removeStep('testStep');

    $step = $this->onboardingSteps->getStep('testStep');

    expect($step)->toBeNull();
});

it('loads registered admin onboarding steps', function() {
    $onboardingSteps = resolve(OnboardingSteps::class);

    expect($onboardingSteps->getStep('admin::themes'))->toBeObject()
        ->and($onboardingSteps->getStep('admin::extensions'))->toBeObject()
        ->and($onboardingSteps->getStep('admin::mail'))->toBeObject()
        ->and($onboardingSteps->getStep('admin::settings'))->toHaveProperties([
            'code', 'label', 'description', 'icon', 'url', 'priority', 'complete',
        ])
        ->and($onboardingSteps->getStep('admin::settings')->complete)->toBeCallable();
});

it('lists onboarding steps correctly', function($steps) {
    $this->onboardingSteps->registerSteps($steps);

    $steps = $this->onboardingSteps->listSteps();

    expect($steps)->toHaveKey('testStep1')
        ->and($steps)->toHaveKey('testStep2');
})->with('onboardingSteps');

it('lists empty onboarding steps when nothing is registered', function() {
    OnboardingSteps::clearCallbacks();
    $extensionManager = mock(ExtensionManager::class);
    app()->instance(ExtensionManager::class, $extensionManager);
    $extensionManager->shouldReceive('getExtensions')->andReturn([
        'testExtension' => new class
        {
            public function registerOnboardingSteps(): string
            {
                return 'not-an-array';
            }
        },
    ]);

    $steps = $this->onboardingSteps->listSteps();

    expect($steps)->toBeEmpty();
});

it('checks if onboarding is completed correctly', function($steps) {
    $this->onboardingSteps->registerSteps($steps);

    expect($this->onboardingSteps->completed())->toBeFalse();

    $class = new class
    {
        public function method(): bool
        {
            return true;
        }
    };

    $steps['testStep1']['complete'] = fn() => true;
    $steps['testStep2']['complete'] = [$class, 'method'];
    $this->onboardingSteps->registerSteps($steps);

    expect($this->onboardingSteps->completed())->toBeTrue();
})->with('onboardingSteps');

it('checks if onboarding is in progress correctly', function($steps) {
    $this->onboardingSteps->registerSteps($steps);

    expect($this->onboardingSteps->inProgress())->toBeTrue();

    $steps['testStep1']['complete'] = fn() => true;
    $steps['testStep2']['complete'] = fn() => true;
    $this->onboardingSteps->registerSteps($steps);

    expect($this->onboardingSteps->inProgress())->toBeFalse();
})->with('onboardingSteps');

it('gets the next incomplete onboarding step correctly', function($steps) {
    $this->onboardingSteps->registerSteps($steps);

    $step = $this->onboardingSteps->nextIncompleteStep();

    expect($step->label)->toBe('Test Step 1');
})->with('onboardingSteps');
