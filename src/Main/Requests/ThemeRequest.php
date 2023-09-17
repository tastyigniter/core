<?php

namespace Igniter\Main\Requests;

use Igniter\System\Classes\FormRequest;

class ThemeRequest extends FormRequest
{
    public function attributes()
    {
        if (!$this->isEditFormContext()) {
            return [];
        }

        return collect($this->fields())->mapWithKeys(function ($config, $field) {
            $dottedName = implode('.', name_to_array($field));
            return [$dottedName => array_get($config, 'label')];
        })->filter()->all();
    }

    public function rules()
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
     *
     * @return array
     */
    public function validationData()
    {
        return array_undot($this->all());
    }

    protected function isEditFormContext()
    {
        return $this->route()->getController()->getFormContext() === 'edit';
    }

    protected function fields()
    {
        return $this->route()->getController()->getFormModel()->getFieldsConfig();
    }
}
