<?php

namespace Igniter\Admin\Traits;

use Closure;
use Igniter\Flame\Exception\FlashException;
use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Exception\ValidationException;
use Igniter\Flame\Igniter;
use Igniter\System\Helpers\ValidationHelper;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Session;

trait ValidatesForm
{
    protected $validateAfterCallback;

    /**
     * Validate the given request with the given rules.
     *
     * @return array|bool
     */
    public function validatePasses($request, array $rules, array $messages = [], array $customAttributes = [])
    {
        $validator = $this->makeValidator($request, $rules, $messages, $customAttributes);

        if ($validator->fails()) {
            $this->flashValidationErrors($validator->errors());

            return false;
        }

        return $validator->validated();
    }

    /**
     * Validate the given request with the given rules.
     *
     * @return array
     */
    public function validate($request, array $rules, array $messages = [], array $customAttributes = [])
    {
        $validator = $this->makeValidator($request, $rules, $messages, $customAttributes);

        if ($validator->fails()) {
            $this->flashValidationErrors($validator->errors());

            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    public function makeValidator($request, array $rules, array $messages = [], array $customAttributes = [])
    {
        $parsed = ValidationHelper::prepareRules($rules);
        $rules = Arr::get($parsed, 'rules', $rules);
        $customAttributes = Arr::get($parsed, 'attributes', $customAttributes);

        $validator = validator()->make(
            $request ?? [], $rules, $messages, $customAttributes
        );

        if ($this->validateAfterCallback instanceof Closure) {
            $validator->after($this->validateAfterCallback);
        }

        return $validator;
    }

    public function parseRules(array $rules)
    {
        if (!isset($rules[0])) {
            return $rules;
        }

        $result = [];
        foreach ($rules as $value) {
            $result[$value[0]] = $value[2] ?? [];
        }

        return $result;
    }

    public function parseAttributes(array $rules)
    {
        if (!isset($rules[0])) {
            return [];
        }

        $result = [];
        foreach ($rules as [$name, $attribute]) {
            $result[$name] = is_lang_key($attribute) ? lang($attribute) : $attribute;
        }

        return $result;
    }

    public function validateAfter(Closure $callback)
    {
        $this->validateAfterCallback = $callback;
    }

    protected function flashValidationErrors($errors)
    {
        $sessionKey = 'errors';

        if (Igniter::runningInAdmin()) {
            $sessionKey = 'admin_errors';
        }

        return Session::flash($sessionKey, $errors);
    }

    protected function validateFormWidget($form, $saveData)
    {
        $validated = [];

        // for backwards support, first of all try and use a rules in the config if we have them
        if ($rules = array_get($form->config, 'rules')) {
            $validated = $this->validate($saveData, $rules,
                array_get($form->config, 'validationMessages', []),
                array_get($form->config, 'validationAttributes', [])
            );
        }

        // if we dont have in config then fallback to a FormRequest class
        if ($requestClass = array_get($this->config, 'request')) {
            if (!class_exists($requestClass)) {
                throw new SystemException(sprintf(lang('igniter::admin.form.request_class_not_found'), $requestClass));
            }

            $validated = array_merge($validated,
                $this->resolveFormRequest($requestClass, function ($request) use ($saveData) {
                    $request->merge($saveData);
                })->validated()
            );
        }

        return $validated ?: $saveData;
    }

    protected function validateFormRequest($requestClass, $model, $callback)
    {
        if (!$requestClass || !class_exists($requestClass)) {
            throw FlashException::error(sprintf(lang('igniter::admin.form.request_class_not_found'), $requestClass));
        }

        return $this->resolveFormRequest($requestClass, $callback)->validated();
    }

    protected function resolveFormRequest($requestClass, $callback)
    {
        app()->resolving($requestClass, $callback);

        return app()->make($requestClass);
    }
}
