<?php

$config['form']['fields'] = [
    'address_id' => [
        'type' => 'hidden',
    ],
    'address_1' => [
        'label' => 'igniter::main.customers.label_address_1',
        'type' => 'text',
    ],
    'address_2' => [
        'label' => 'igniter::main.customers.label_address_2',
        'type' => 'text',
    ],
    'city' => [
        'label' => 'igniter::main.customers.label_city',
        'type' => 'text',
    ],
    'state' => [
        'label' => 'igniter::main.customers.label_state',
        'type' => 'text',
    ],
    'postcode' => [
        'label' => 'igniter::main.customers.label_postcode',
        'type' => 'text',
    ],
];

return $config;
