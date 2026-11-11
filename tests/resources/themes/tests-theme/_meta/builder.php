<?php

declare(strict_types=1);

return [
    'pages' => [
        'home' => [
            'label' => 'Home',
            'default' => [
                [
                    'id' => 's_home',
                    'layout' => 'cols-1',
                    'columns' => [
                        ['blocks' => [['id' => 'b_welcome', 'code' => 'text', 'settings' => ['title' => 'Welcome']]]],
                    ],
                ],
            ],
        ],
        'checkout' => [
            'label' => 'Checkout',
            'required' => ['wide-form'],
        ],
    ],
];
