<?php

$config['list']['filter'] = [
    'scopes' => [
        'type' => [
            'label' => 'lang:igniter::admin.statuses.text_filter_status',
            'type' => 'select', // checkbox, switch, date, daterange
            'conditions' => 'status_for = :filtered',
            'options' => [
                'order' => 'lang:igniter::admin.statuses.text_order',
                'reservation' => 'lang:igniter::admin.statuses.text_reservation',
            ],
        ],
    ],
];

$config['list']['toolbar'] = [
    'buttons' => [
        'create' => [
            'label' => 'lang:igniter::admin.button_new',
            'class' => 'btn btn-primary',
            'href' => 'statuses/create',
        ],
    ],
];

$config['list']['bulkActions'] = [
    'delete' => [
        'label' => 'lang:igniter::admin.button_delete',
        'class' => 'btn btn-light text-danger',
        'data-request-confirm' => 'lang:igniter::admin.alert_warning_confirm',
    ],
];

$config['list']['columns'] = [
    'edit' => [
        'type' => 'button',
        'iconCssClass' => 'fa fa-pencil',
        'attributes' => [
            'class' => 'btn btn-edit',
            'href' => 'statuses/edit/{status_id}',
        ],
    ],
    'status_name' => [
        'label' => 'lang:igniter::admin.label_name',
        'type' => 'text', // number, switch, date_time, time, date, timesince, select, relation, partial
        'searchable' => true,
    ],
    'status_comment' => [
        'label' => 'lang:igniter::admin.statuses.column_comment',
        'type' => 'text',
        'searchable' => true,
    ],
    'status_for_name' => [
        'label' => 'lang:igniter::admin.label_type',
        'type' => 'text',
        'sortable' => false,
    ],
    'notify_customer' => [
        'label' => 'lang:igniter::admin.statuses.column_notify',
        'type' => 'switch',
        'offText' => 'lang:igniter::admin.text_no',
        'onText' => 'lang:igniter::admin.text_yes',
    ],
    'status_id' => [
        'label' => 'lang:igniter::admin.column_id',
        'invisible' => true,
    ],
    'created_at' => [
        'label' => 'lang:igniter::admin.column_date_added',
        'invisible' => true,
        'type' => 'datetime',
    ],
    'updated_at' => [
        'label' => 'lang:igniter::admin.column_date_updated',
        'invisible' => true,
        'type' => 'datetime',
    ],
];

$config['form']['toolbar'] = [
    'buttons' => [
        'save' => [
            'label' => 'lang:igniter::admin.button_save',
            'context' => ['create', 'edit'],
            'partial' => 'form/toolbar_save_button',
            'class' => 'btn btn-primary',
            'data-request' => 'onSave',
            'data-progress-indicator' => 'igniter::admin.text_saving',
        ],
        'delete' => [
            'label' => 'lang:igniter::admin.button_icon_delete',
            'class' => 'btn btn-danger',
            'data-request' => 'onDelete',
            'data-request-data' => "_method:'DELETE'",
            'data-request-confirm' => 'lang:igniter::admin.alert_warning_confirm',
            'data-progress-indicator' => 'igniter::admin.text_deleting',
            'context' => ['edit'],
        ],
    ],
];

$config['form']['fields'] = [
    'status_name' => [
        'label' => 'lang:igniter::admin.label_name',
        'type' => 'text',
        'span' => 'left',
    ],
    'status_for' => [
        'label' => 'lang:igniter::admin.statuses.label_for',
        'type' => 'radiotoggle',
        'span' => 'right',
        'cssClass' => 'flex-width',
        'placeholder' => 'lang:igniter::admin.text_please_select',
        'options' => 'getStatusForDropdownOptions',
    ],
    'status_color' => [
        'label' => 'lang:igniter::admin.statuses.label_color',
        'type' => 'colorpicker',
        'span' => 'right',
        'cssClass' => 'flex-width',
    ],
    'status_comment' => [
        'label' => 'lang:igniter::admin.statuses.label_comment',
        'type' => 'textarea',
    ],
    'notify_customer' => [
        'label' => 'lang:igniter::admin.statuses.label_notify',
        'type' => 'switch',
        'default' => true,
        'onText' => 'lang:igniter::admin.text_no',
        'offText' => 'lang:igniter::admin.text_yes',
        'comment' => 'lang:igniter::admin.statuses.help_notify',
    ],
];

return $config;
