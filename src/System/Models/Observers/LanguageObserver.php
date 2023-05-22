<?php

namespace Igniter\System\Models\Observers;

use Igniter\System\Models\Language;

class LanguageObserver
{
    protected function saved(Language $language)
    {
        Language::applySupportedLanguages();

        $language->restorePurgedValues();

        if (array_key_exists('translations', $attributes = $language->getAttributes())) {
            $language->addTranslations((array)array_get($attributes, 'translations', []));
        }
    }
}