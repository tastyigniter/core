<?php

$config['list']['filter'] = [
    'search' => [
        'prompt' => 'lang:igniter::system.languages.text_filter_search',
        'mode' => 'all', // or any, exact
    ],
    'scopes' => [
        'status' => [
            'label' => 'lang:igniter::admin.text_filter_status',
            'type' => 'switch',
            'conditions' => 'status = :filtered',
        ],
    ],
];

$config['list']['toolbar'] = [
    'buttons' => [
        'create' => [
            'label' => 'lang:igniter::admin.button_new',
            'class' => 'btn btn-primary',
            'href' => 'languages/create',
        ],
    ],
];

$config['list']['bulkActions'] = [
    'status' => [
        'label' => 'lang:igniter::admin.list.actions.label_status',
        'type' => 'dropdown',
        'class' => 'btn btn-light',
        'statusColumn' => 'status',
        'menuItems' => [
            'enable' => [
                'label' => 'lang:igniter::admin.list.actions.label_enable',
                'type' => 'button',
                'class' => 'dropdown-item',
            ],
            'disable' => [
                'label' => 'lang:igniter::admin.list.actions.label_disable',
                'type' => 'button',
                'class' => 'dropdown-item text-danger',
            ],
        ],
    ],
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
            'href' => 'languages/edit/{language_id}',
        ],
    ],
    'default' => [
        'type' => 'button',
        'iconCssClass' => 'fa fa-star-o',
        'attributes' => [
            'class' => 'btn btn-light text-warning',
            'data-request' => 'onSetDefault',
            'data-request-data' => "default:'{code}'",
        ],
    ],
    'name' => [
        'label' => 'lang:igniter::admin.label_name',
        'type' => 'text',
        'searchable' => true,
    ],
    'code' => [
        'label' => 'lang:igniter::system.languages.column_code',
        'type' => 'text',
        'searchable' => true,
    ],
    'status' => [
        'label' => 'lang:igniter::system.languages.column_status',
        'type' => 'switch',
        'searchable' => true,
    ],
    'language_id' => [
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
    'code' => [
        'label' => 'lang:igniter::system.languages.label_code',
        'type' => 'text',
        'span' => 'none',
        'cssClass' => 'col-md-4',
    ],
    'name' => [
        'label' => 'lang:igniter::admin.label_name',
        'type' => 'text',
        'span' => 'none',
        'cssClass' => 'col-md-4',
    ],
    'status' => [
        'label' => 'lang:igniter::admin.label_status',
        'type' => 'switch',
        'span' => 'none',
        'cssClass' => 'col-md-4',
        'default' => true,
    ],
    'section' => [
        'type' => 'section',
        'comment' => 'lang:igniter::system.languages.help_language',
    ],
];

$config['form']['tabs'] = [
    'defaultTab' => 'lang:igniter::system.languages.text_tab_general',
    'fields' => [
        '_group' => [
            'tab' => 'lang:igniter::system.languages.text_tab_files',
            'type' => 'select',
            'context' => 'edit',
            'options' => 'getGroupOptions',
            'span' => 'none',
            'placeholder' => 'igniter::system.languages.text_filter_file',
            'cssClass' => 'col-md-4',
            'attributes' => [
                'data-request' => 'onSubmitFilter',
            ],
        ],
        '_search' => [
            'tab' => 'lang:igniter::system.languages.text_tab_files',
            'type' => 'text',
            'context' => 'edit',
            'span' => 'none',
            'cssClass' => 'col-md-4',
            'placeholder' => lang('igniter::system.languages.text_filter_translations'),
            'attributes' => [
                'data-control' => 'search-translations',
                'data-request' => 'onSubmitFilter',
            ],
        ],
        '_filter' => [
            'tab' => 'lang:igniter::system.languages.text_tab_files',
            'type' => 'radiotoggle',
            'context' => 'edit',
            'span' => 'none',
            'cssClass' => 'col-md-4',
            'default' => 'changed',
            'options' => [
                'all' => 'All',
                'changed' => 'Translated',
                'unchanged' => 'Untranslated',
            ],
            'attributes' => [
                'data-control' => 'string-filter',
                'data-request' => 'onSubmitFilter',
            ],
        ],
        'translations' => [
            'tab' => 'lang:igniter::system.languages.text_tab_files',
            'type' => 'partial',
            'path' => 'translationseditor',
            'context' => 'edit',
        ],
    ],
];

return $config;
