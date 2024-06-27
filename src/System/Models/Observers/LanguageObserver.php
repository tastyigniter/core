<?php

namespace Igniter\System\Models\Observers;

use Igniter\System\Models\Language;

class LanguageObserver
{
    public function creating(Language $language)
    {
        $language->idiom = $language->code;
    }

    public function saved(Language $language)
    {
        Language::applySupportedLanguages();

        $language->restorePurgedValues();

        if (array_key_exists('translations', $attributes = $language->getAttributes())) {
            $language->addTranslations((array)array_get($attributes, 'translations', []));
        }
    }
}
