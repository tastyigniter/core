<?php

return [
    'list' => [
        'filter' => [
            'search' => [
                'prompt' => 'lang:igniter::system.notifications.text_filter_search',
                'mode' => 'all', // or any, exact
            ],
        ],
        'toolbar' => [
            'buttons' => [
                'markAsRead' => [
                    'label' => 'lang:igniter::system.notifications.button_mark_as_read',
                    'class' => 'btn btn-primary',
                    'data-request' => 'onMarkAsRead',
                ],
            ],
        ],
        'columns' => [
            'created_at' => [
                'type' => 'text',
                'searchable' => true,
            ],
            'read_at' => [
                'type' => 'text',
                'searchable' => true,
            ],
            'data' => [
                'type' => 'text',
                'searchable' => true,
            ],
            'type' => [
                'type' => 'text',
                'searchable' => true,
            ],
            'id' => [
                'invisible' => true,
            ],
        ],
    ],
];
