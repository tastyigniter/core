<?php

/**
 * Settings of a theme page file, edited from the page builder's pages list.
 * Extensions add their own tabs to this form, e.g. SEO Manager's SEO tab.
 */
return [
    'form' => [
        'tabs' => [
            'fields' => [
                'settings[title]' => [
                    'label' => 'igniter::system.themes.label_title',
                    'tab' => 'igniter::system.themes.text_tab_meta',
                    'type' => 'text',
                    'span' => 'left',
                ],
                'settings[permalink]' => [
                    'tab' => 'igniter::system.themes.text_tab_meta',
                    'label' => 'igniter::system.themes.label_permalink',
                    'type' => 'text',
                    'span' => 'right',
                ],
                'settings[layout]' => [
                    'tab' => 'igniter::system.themes.text_tab_meta',
                    'label' => 'igniter::system.themes.label_layout',
                    'type' => 'select',
                    'options' => 'getLayoutOptions',
                    'placeholder' => 'lang:igniter::admin.text_please_select',
                ],
                'settings[description]' => [
                    'tab' => 'igniter::system.themes.text_tab_meta',
                    'label' => 'lang:igniter::admin.label_description',
                    'type' => 'text',
                ],
                'settings[isHidden]' => [
                    'tab' => 'igniter::system.themes.text_tab_meta',
                    'label' => 'igniter::system.themes.label_hidden',
                    'type' => 'switch',
                    'span' => 'left',
                    'comment' => 'igniter::system.themes.help_hidden',
                ],
            ],
        ],
    ],
];
