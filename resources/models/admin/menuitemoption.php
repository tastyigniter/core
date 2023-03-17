<?php

$config['form']['fields'] = [
    'option_name' => [
        'label' => 'lang:igniter::admin.menu_options.label_option_group_name',
        'type' => 'text',
        'span' => 'left',
        'disabled' => true,
    ],
    'display_type' => [
        'label' => 'lang:igniter::admin.menu_options.label_option_display_type',
        'type' => 'text',
        'span' => 'right',
        'disabled' => true,
    ],
    'is_required' => [
        'label' => 'lang:igniter::admin.menu_options.label_option_required',
        'type' => 'switch',
    ],
    'min_selected' => [
        'label' => 'lang:igniter::admin.menu_options.label_min_selected',
        'type' => 'number',
        'span' => 'left',
        'comment' => 'lang:igniter::admin.menu_options.help_min_selected',
    ],
    'max_selected' => [
        'label' => 'lang:igniter::admin.menu_options.label_max_selected',
        'type' => 'number',
        'span' => 'right',
        'comment' => 'lang:igniter::admin.menu_options.help_max_selected',
    ],
    'menu_option_values' => [
        'type' => 'repeater',
        'form' => 'menuitemoptionvalue',
        'valueFrom' => 'option_values',
        'sortable' => true,
        'showAddButton' => false,
        'showRemoveButton' => false,
    ],
];

return $config;
