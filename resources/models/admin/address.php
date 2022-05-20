<?php
$config['form']['fields'] = [
    'address_id' => [
        'type' => 'hidden',
    ],
    'address_1' => [
        'label' => 'igniter::admin.customers.label_address_1',
        'type' => 'text',
    ],
    'address_2' => [
        'label' => 'igniter::admin.customers.label_address_2',
        'type' => 'text',
    ],
    'city' => [
        'label' => 'igniter::admin.customers.label_city',
        'type' => 'text',
    ],
    'state' => [
        'label' => 'igniter::admin.customers.label_state',
        'type' => 'text',
    ],
    'postcode' => [
        'label' => 'igniter::admin.customers.label_postcode',
        'type' => 'text',
    ],
    'country_id' => [
        'label' => 'igniter::admin.customers.label_country',
        'type' => 'select',
        'options' => [\Igniter\System\Models\Country::class, 'getDropdownOptions'],
    ],
];

return $config;
