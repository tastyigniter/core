<?php

namespace Tests\Admin\Classes;

use Igniter\Admin\Classes\OnboardingSteps;

it('registers an onboarding step', function () {
    $onboardingSteps = resolve(OnboardingSteps::class);

    $onboardingSteps->registerSteps([
        'test' => [
            'label' => 'Test',
            'description' => 'Test description',
            'icon' => 'fa fa-test',
            'url' => 'test',
            'priority' => 100,
            'complete' => function () {
                return TRUE;
            },
            'completed' => function () {
                return TRUE;
            },
        ]
    ]);

    $steps = $onboardingSteps->listSteps();

    expect($steps['test'])->toHaveProperties([
        'code', 'label', 'description', 'icon', 'url', 'priority', 'complete', 'completed',
    ])
        ->and($steps['test']->complete)->toBeCallable()
        ->and($steps['test']->completed)->toBeCallable();
});

it('loads registered admin onboarding steps', function () {
    $onboardingSteps = resolve(OnboardingSteps::class);

    expect($onboardingSteps->getStep('admin::themes'))->toBeObject()
        ->and($onboardingSteps->getStep('admin::extensions'))->toBeObject()
        ->and($onboardingSteps->getStep('admin::mail'))->toBeObject()
        ->and($onboardingSteps->getStep('admin::settings'))->toHaveProperties([
            'code', 'label', 'description', 'icon', 'url', 'priority', 'complete', 'completed',
        ])
        ->and($onboardingSteps->getStep('admin::settings')->complete)->toBeCallable()
        ->and($onboardingSteps->getStep('admin::settings')->completed)->toBeCallable();
});