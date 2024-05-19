<?php

namespace Igniter\System\Providers;

use Igniter\System\Classes\ExtensionManager;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class ValidationServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerValidator();
    }

    protected function registerValidator()
    {
        $this->app->resolving('validator', function($validator) {
            $validator->extend('extensions', function($attribute, $value, $parameters) {
                $extension = strtolower($value->getClientOriginalExtension());

                return in_array($extension, $parameters);
            });

            $validator->replacer('extensions', function($message, $attribute, $rule, $parameters) {
                return strtr($message, [':values' => implode(', ', $parameters)]);
            });

            $extensions = resolve(ExtensionManager::class)->getRegistrationMethodValues('registerValidationRules');
            foreach ($extensions as $validators) {
                if (!is_array($validators) || empty($validators)) {
                    continue;
                }

                foreach ($validators as $name => $validatorExtension) {
                    Validator::extend($name, $validatorExtension);
                }
            }
        });
    }
}