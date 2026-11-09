<?php

return [
    'form' => [
        'tabs' => [
            'fields' => [
                'markup' => [
                    'tab' => 'lang:igniter::system.themes.text_tab_markup',
                    'type' => 'codeeditor',
                    'mode' => 'application/x-httpd-php',
                    'readOnly' => true,
                ],
            ],
        ],
        'rules' => [],
        'validationAttributes' => [
            'markup' => lang('igniter::system.themes.text_tab_markup'),
        ],
    ],
];
