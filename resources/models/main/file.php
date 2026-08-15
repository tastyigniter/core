<?php

use Igniter\System\Rules\SafeThemeTemplateContent;

return [
    'form' => [
        'tabs' => [
            'fields' => [
                'markup' => [
                    'tab' => 'lang:igniter::system.themes.text_tab_markup',
                    'type' => 'codeeditor',
                    'mode' => 'application/x-httpd-php',
                ],
            ],
        ],
        'rules' => [
            'markup' => ['string', new SafeThemeTemplateContent],
        ],
        'validationAttributes' => [
            'markup' => lang('igniter::system.themes.text_tab_markup'),
        ],
    ],
];
