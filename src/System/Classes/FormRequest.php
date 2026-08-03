<?php

declare(strict_types=1);

namespace Igniter\System\Classes;

use Igniter\Flame\Traits\EventEmitter;
use Igniter\System\Helpers\ValidationHelper;
use Igniter\System\Models\Currency;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest as BaseFormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Override;
use stdClass;

class FormRequest extends BaseFormRequest
{
    use EventEmitter;

    /**
     * Create the default validator instance.
     */
    #[Override]
    protected function createDefaultValidator(Factory $factory): Validator
    {
        $registeredRules = $this->container->call([$this, 'rules']);
        $parsedRules = ValidationHelper::prepareRules($registeredRules);

        $dataHolder = new stdClass;
        $dataHolder->data = $this->validationData();
        $dataHolder->rules = Arr::get($parsedRules, 'rules', $registeredRules);
        $dataHolder->messages = array_merge(Arr::get($parsedRules, 'messages', []), $this->messages());
        $dataHolder->attributes = array_merge(Arr::get($parsedRules, 'attributes', []), $this->attributes());

        $dataHolder->data = $this->normalizeCurrencyFormattedNumbers($dataHolder->data, $dataHolder->rules);

        $this->fireSystemEvent('system.formRequest.extendValidator', [$dataHolder]);

        return $factory->make(
            $dataHolder->data,
            $dataHolder->rules,
            $dataHolder->messages,
            $dataHolder->attributes,
        );
    }

    /**
     * Handle a failed validation attempt.
     */
    #[Override]
    protected function failedValidation(Validator $validator): never
    {
        throw new ValidationException($validator);
    }

    protected function getRecordId(): int|string|null
    {
        return ($slug = $this->route('slug'))
            ? str_after($slug, '/') : null;
    }

    /**
     * Accept numeric input in the format the admin renders it in.
     *
     * The currency form field displays values using the default currency's
     * decimal/thousand signs ("11,50" or "1.234,56" when the decimal sign is
     * a comma), but the `numeric` rule only accepts machine format — so the
     * field renders a value its own form refuses to save. Convert values
     * matching the configured currency format back to machine format for
     * every explicitly `numeric`-validated field, before the validator is
     * built. No-op when the configured decimal sign is already a dot, so
     * nothing changes for default installs.
     */
    protected function normalizeCurrencyFormattedNumbers(array $data, array $rules): array
    {
        $decimalSign = ($currency = Currency::getDefault())?->decimal_sign ?: '.';
        if ($decimalSign === '.') {
            return $data;
        }

        $thousandSign = (string)($currency->thousand_sign ?? '');
        if ($thousandSign === $decimalSign) {
            $thousandSign = '';
        }

        $pattern = sprintf(
            '/^-?(?:\d{1,3}(?:%s\d{3})+|\d+)%s\d+$/',
            preg_quote($thousandSign, '/'),
            preg_quote($decimalSign, '/'),
        );

        foreach ($rules as $key => $fieldRules) {
            if (str_contains((string)$key, '*')) {
                continue;
            }

            $fieldRules = is_array($fieldRules) ? $fieldRules : explode('|', (string)$fieldRules);
            if (!in_array('numeric', $fieldRules, true)) {
                continue;
            }

            $value = Arr::get($data, $key);
            if (is_string($value) && preg_match($pattern, $value)) {
                $value = $thousandSign === '' ? $value : str_replace($thousandSign, '', $value);
                Arr::set($data, $key, str_replace($decimalSign, '.', $value));
            }
        }

        return $data;
    }
}
