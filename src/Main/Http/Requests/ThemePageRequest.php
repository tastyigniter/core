<?php

declare(strict_types=1);

namespace Igniter\Main\Http\Requests;

use Override;

class ThemePageRequest extends ThemeRequest
{
    #[Override]
    public function attributes(): array
    {
        return [
            'settings.title' => lang('igniter::system.themes.label_title'),
            'settings.permalink' => lang('igniter::system.themes.label_permalink'),
            'settings.layout' => lang('igniter::system.themes.label_layout'),
            'settings.description' => lang('igniter::admin.label_description'),
            'settings.isHidden' => lang('igniter::system.themes.label_hidden'),
        ];
    }

    #[Override]
    public function rules(): array
    {
        return [
            // Pages without a file have no address and keep their own title when this is empty.
            'settings.title' => ['required_with:settings.permalink', 'nullable', 'string', 'max:160'],
            'settings.permalink' => ['sometimes', 'required', 'string', 'max:255', 'regex:/^\/[^\s]*$/'],
            'settings.layout' => ['nullable', 'string', 'max:128'],
            'settings.description' => ['nullable', 'string', 'max:255'],
            'settings.isHidden' => ['boolean'],
        ];
    }

    #[Override]
    public function validationData(): array
    {
        return array_undot((array)$this->input('PageSettings', []));
    }
}
