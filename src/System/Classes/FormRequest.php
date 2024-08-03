<?php

namespace Igniter\System\Classes;

use Igniter\Flame\Traits\EventEmitter;
use Igniter\System\Helpers\ValidationHelper;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest as BaseFormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class FormRequest extends BaseFormRequest
{
    use EventEmitter;

    /**
     * Create the default validator instance.
     */
    protected function createDefaultValidator(Factory $factory): Validator
    {
        $registeredRules = $this->container->call([$this, 'rules']);
        $parsedRules = ValidationHelper::prepareRules($registeredRules);

        $dataHolder = new \stdClass;
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
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator);
    }

    protected function getRecordId(): ?string
    {
        return ($slug = $this->route('slug'))
            ? str_after($slug, '/') : null;
    }
}
