<?php

declare(strict_types=1);

namespace Igniter\System\Rules;

use Closure;
use Igniter\System\Models\Currency as CurrencyModel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Arr;
use Illuminate\Validation\Validator;

class Currency implements ValidationRule, ValidatorAwareRule
{
    protected ?Validator $validator = null;

    public function setValidator(Validator $validator): static
    {
        $this->validator = $validator;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->passes($attribute, $value)) {
            $fail('validation.numeric');
        }
    }

    public function passes(string $attribute, mixed $value): bool
    {
        if (is_int($value) || is_float($value)) {
            return true;
        }

        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        if (is_numeric($value)) {
            return true;
        }

        $normalized = $this->normalizeLocalizedNumber($value);
        if ($normalized === null) {
            return false;
        }

        if ($this->validator) {
            $data = $this->validator->getData();
            Arr::set($data, $attribute, $normalized);
            $this->validator->setData($data);
        }

        return true;
    }

    /**
     * Convert a value written with the default currency's decimal/thousand
     * signs ("11,50" or "1.234,56") back to machine format. Returns null
     * when the value does not match that format — including when the
     * configured decimal sign is already a dot, so default installs keep
     * rejecting comma decimals.
     */
    protected function normalizeLocalizedNumber(string $value): ?string
    {
        $currency = CurrencyModel::getDefault();
        $decimalSign = $currency?->decimal_sign ?: '.';
        if ($decimalSign === '.') {
            return null;
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

        if (!preg_match($pattern, $value)) {
            return null;
        }

        if ($thousandSign !== '') {
            $value = str_replace($thousandSign, '', $value);
        }

        return str_replace($decimalSign, '.', $value);
    }
}
