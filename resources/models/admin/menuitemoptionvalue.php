<?php

$config['form']['fields'] = [
    'menu_option_value_id' => [
        'type' => 'hidden',
    ],
    'priority' => [
        'type' => 'hidden',
    ],
    'name' => [
        'label' => 'lang:igniter::admin.menu_options.label_option_name',
        'type' => 'text',
        'disabled' => true,
    ],
    'price' => [
        'label' => 'lang:igniter::admin.menu_options.label_option_price',
        'disabled' => true,
        'type' => 'currency',
    ],
    'override_price' => [
        'label' => 'lang:igniter::admin.menu_options.label_new_price',
        'type' => 'currency',
    ],
    'is_default' => [
        'label' => 'lang:igniter::admin.menu_options.label_option_default_value',
        'type' => 'checkbox',
        'options' => [],
    ],
    'is_enabled' => [
        'label' => 'lang:igniter::admin.menu_options.label_option_enabled',
        'type' => 'checkbox',
        'options' => [],
    ],
];

return $config;
