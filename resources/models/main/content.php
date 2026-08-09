<?php

use Igniter\System\Rules\SafeThemeTemplateContent;

return [
    'form' => [
        'tabs' => [
            'fields' => [
                'markup' => [
                    'tab' => 'igniter::system.themes.text_tab_markup',
                    'type' => 'codeeditor',
                    'mode' => 'html',
                ],
                'settings[description]' => [
                    'tab' => 'igniter::system.themes.text_tab_meta',
                    'label' => 'lang:igniter::admin.label_description',
                    'type' => 'text',
                ],
            ],
        ],
        'rules' => [
            'markup' => ['string', new SafeThemeTemplateContent],
            'settings.description' => ['max:255', new SafeThemeTemplateContent],
        ],
        'validationAttributes' => [
            'markup' => lang('igniter::system.themes.text_tab_markup'),
            'settings.description' => lang('igniter::admin.label_description'),
        ],
    ],
];
