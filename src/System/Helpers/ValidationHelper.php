<?php

namespace Igniter\System\Helpers;

class ValidationHelper
{
    /**
     * Returns shared view variables, this should be used for simple rendering cycles.
     * Such as content blocks and mail templates.
     *
     * @return array
     */
    public static function prepareRules(array $rules)
    {
        $result = [];

        if (!isset($rules[0])) {
            return $result;
        }

        foreach ($rules as $value) {
            $name = $value[0] ?? '';
            if (isset($value[2])) {
                $result['rules'][$name] = is_string($value[2]) ? explode('|', $value[2]) : $value[2];
            }

            if (isset($value[1])) {
                $result['attributes'][$name] = is_lang_key($value[1]) ? lang($value[1]) : $value[1];
            }
        }

        return $result;
    }
}
