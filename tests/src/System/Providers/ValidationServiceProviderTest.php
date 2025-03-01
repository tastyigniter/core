<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Providers;

use Igniter\System\Classes\ExtensionManager;
use Igniter\System\Providers\ValidationServiceProvider;

it('registers custom validation rules from extensions', function() {
    $extensionManager = mock(ExtensionManager::class);
    app()->instance(ExtensionManager::class, $extensionManager);
    $extensionManager->shouldReceive('getRegistrationMethodValues')
        ->with('registerValidationRules')
        ->andReturn([
            ['custom_rule' => fn($attribute, $value, $parameters): bool => $value === 'custom'],
        ]);

    (new ValidationServiceProvider(app()))->register();

    app()->forgetInstance('validator');
    $validator = resolve('validator')->make(['field' => 'custom'], ['field' => 'custom_rule']);
    expect($validator->passes())->toBeTrue();
});
