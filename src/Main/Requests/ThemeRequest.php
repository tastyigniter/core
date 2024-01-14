<?php

namespace Igniter\Main\Requests;

use Igniter\System\Classes\FormRequest;

class ThemeRequest extends FormRequest
{
    public function attributes(): array
    {
        if (!$this->isEditFormContext()) {
            return [];
        }

        return collect($this->fields())->mapWithKeys(function ($config, $field) {
            $dottedName = implode('.', name_to_array($field));

            return [$dottedName => array_get($config, 'label')];
        })->filter()->all();
    }

    public function rules(): array
    {
        if (!$this->isEditFormContext()) {
            return [];
        }

        return collect($this->fields())->mapWithKeys(function ($config, $field) {
            $dottedName = implode('.', name_to_array($field));

            return [$dottedName => array_get($config, 'rules')];
        })->filter()->all();
    }

    /**
     * Get data to be validated from the request.
     */
    public function validationData(): array
    {
        return array_undot($this->all());
    }

    protected function isEditFormContext(): bool
    {
        return $this->route()->getController()->getFormContext() === 'edit';
    }

    protected function fields(): array
    {
        return $this->route()->getController()->getFormModel()->getFieldsConfig();
    }
}
