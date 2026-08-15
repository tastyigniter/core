<?php

declare(strict_types=1);

namespace Igniter\System\Providers;

use Igniter\System\Classes\ExtensionManager;
use Igniter\System\Rules\Currency;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Validator;
use Override;

class ValidationServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->registerValidator();
    }

    protected function registerValidator()
    {
        $this->app->resolving('validator', function($validator) {
            $validator->extend('currency', function(string $attribute, mixed $value, array $parameters, Validator $validatorInstance): bool {
                $rule = new Currency;
                $rule->setValidator($validatorInstance);

                return $rule->passes($attribute, $value);
            }, trans('validation.numeric'));

            $extensions = resolve(ExtensionManager::class)->getRegistrationMethodValues('registerValidationRules');
            foreach ($extensions as $validators) {
                if (!is_array($validators) || empty($validators)) {
                    continue;
                }

                foreach ($validators as $name => $validatorExtension) {
                    $validator->extend($name, $validatorExtension);
                }
            }
        });
    }
}
