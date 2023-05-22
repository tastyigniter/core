<?php

namespace Igniter\System\Classes;

use Igniter\Flame\Exception\ValidationException;
use Igniter\Flame\Traits\EventEmitter;
use Igniter\System\Helpers\ValidationHelper;
use Igniter\System\Traits\RuleInjector;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest as BaseFormRequest;
use Illuminate\Support\Arr;

class FormRequest extends BaseFormRequest
{
    use EventEmitter;

    /**
     * Create the default validator instance.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function createDefaultValidator(Factory $factory)
    {
        $registeredRules = $this->container->call([$this, 'rules']);
        $parsedRules = ValidationHelper::prepareRules($registeredRules);

        $dataHolder = new \stdClass();
        $dataHolder->data = $this->validationData();
        $dataHolder->rules = Arr::get($parsedRules, 'rules', $registeredRules);
        $dataHolder->messages = Arr::get($parsedRules, 'messages', $this->messages());
        $dataHolder->attributes = Arr::get($parsedRules, 'attributes', $this->attributes());

        $this->fireSystemEvent('system.formRequest.extendValidator', [$dataHolder]);

        return $factory->make(
            $dataHolder->data,
            $dataHolder->rules,
            $dataHolder->messages,
            $dataHolder->attributes
        );
    }

    /**
     * Get data to be validated from the request.
     *
     * @return array
     */
    public function validationData()
    {
        return $this->all();
    }

    /**
     * Handle a failed validation attempt.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator);
    }
}
