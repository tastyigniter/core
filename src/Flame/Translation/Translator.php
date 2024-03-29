<?php

namespace Igniter\Flame\Translation;

use Illuminate\Support\Str;
use Illuminate\Translation\Translator as BaseTranslator;

class Translator extends BaseTranslator
{
    protected $replaceNamespaces = [
        [
            'admin::lang.',
            'main::lang.',
            'system::lang.',
        ],
        [
            'igniter::admin.',
            'igniter::main.',
            'igniter::system.',
        ],
    ];

    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        if (Str::startsWith($key, 'lang:')) {
            $key = substr($key, 5);
        }

        if (Str::startsWith($key, $this->replaceNamespaces[0])) {
            $key = Str::replace($this->replaceNamespaces[0], $this->replaceNamespaces[1], $key);
        }

        if ($line = $this->getValidationKey($key, $replace, $locale)) {
            return $line;
        }

        return parent::get($key, $replace, $locale, $fallback);
    }

    /**
     * Get the validation translation.
     *
     * @param  string $key
     * @param  array $replace
     * @param  string $locale
     * @return string
     */
    protected function getValidationKey($key, $replace, $locale)
    {
        if (
            starts_with($key, 'validation.')
            && !starts_with($key, 'validation.custom.')
            && !starts_with($key, 'validation.attributes.')
        ) {
            $systemKey = 'system::'.$key;
            $line = $this->get($systemKey, $replace, $locale);
            if ($line !== $systemKey) {
                return $line;
            }
        }
    }
}
