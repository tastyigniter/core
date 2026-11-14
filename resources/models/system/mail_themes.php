<?php

$body = 'igniter::system.mail_builder.text_theme_group_body';
$buttons = 'igniter::system.mail_builder.text_theme_group_buttons';
$text = 'igniter::system.mail_builder.text_theme_group_text';
$borders = 'igniter::system.mail_builder.text_theme_group_borders';

$config['form']['fields'] = [
    'body_bg' => [
        'label' => 'igniter::system.mail_builder.label_theme_body_bg',
        'type' => 'colorpicker',
        'group' => $body,
    ],
    'content_bg' => [
        'label' => 'igniter::system.mail_builder.label_theme_content_bg',
        'type' => 'colorpicker',
        'group' => $body,
    ],
    'content_inner_bg' => [
        'label' => 'igniter::system.mail_builder.label_theme_content_inner_bg',
        'type' => 'colorpicker',
        'group' => $body,
    ],
    'panel_bg' => [
        'label' => 'igniter::system.mail_builder.label_theme_panel_bg',
        'type' => 'colorpicker',
        'group' => $body,
    ],
    'promotion_bg' => [
        'label' => 'igniter::system.mail_builder.label_theme_promotion_bg',
        'type' => 'colorpicker',
        'group' => $body,
    ],
    'button_text_color' => [
        'label' => 'igniter::system.mail_builder.label_theme_button_text_color',
        'type' => 'colorpicker',
        'group' => $buttons,
    ],
    'button_primary_bg' => [
        'label' => 'igniter::system.mail_builder.label_theme_button_primary_bg',
        'type' => 'colorpicker',
        'group' => $buttons,
    ],
    'button_positive_bg' => [
        'label' => 'igniter::system.mail_builder.label_theme_button_positive_bg',
        'type' => 'colorpicker',
        'group' => $buttons,
    ],
    'button_negative_bg' => [
        'label' => 'igniter::system.mail_builder.label_theme_button_negative_bg',
        'type' => 'colorpicker',
        'group' => $buttons,
    ],
    'header_color' => [
        'label' => 'igniter::system.mail_builder.label_theme_header_color',
        'type' => 'colorpicker',
        'group' => $text,
    ],
    'heading_color' => [
        'label' => 'igniter::system.mail_builder.label_theme_heading_color',
        'type' => 'colorpicker',
        'group' => $text,
    ],
    'text_color' => [
        'label' => 'igniter::system.mail_builder.label_theme_text_color',
        'type' => 'colorpicker',
        'group' => $text,
    ],
    'link_color' => [
        'label' => 'igniter::system.mail_builder.label_theme_link_color',
        'type' => 'colorpicker',
        'group' => $text,
    ],
    'footer_color' => [
        'label' => 'igniter::system.mail_builder.label_theme_footer_color',
        'type' => 'colorpicker',
        'group' => $text,
    ],
    'body_border_color' => [
        'label' => 'igniter::system.mail_builder.label_theme_body_border_color',
        'type' => 'colorpicker',
        'group' => $borders,
    ],
    'subcopy_border_color' => [
        'label' => 'igniter::system.mail_builder.label_theme_subcopy_border_color',
        'type' => 'colorpicker',
        'group' => $borders,
    ],
    'table_border_color' => [
        'label' => 'igniter::system.mail_builder.label_theme_table_border_color',
        'type' => 'colorpicker',
        'group' => $borders,
    ],
    'promotion_border_color' => [
        'label' => 'igniter::system.mail_builder.label_theme_promotion_border_color',
        'type' => 'colorpicker',
        'group' => $borders,
    ],
];

return $config;
