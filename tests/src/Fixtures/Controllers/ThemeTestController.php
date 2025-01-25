<?php

namespace Igniter\Tests\Fixtures\Controllers;

class ThemeTestController
{
    public static $context = 'edit';

    public function index() {}

    public function getFormContext()
    {
        return static::$context;
    }

    public function getFormModel()
    {
        return new class
        {
            public function getFieldsConfig()
            {
                return [
                    'theme_website' => [
                        'label' => lang('igniter.main::default.theme_website_label'),
                        'rules' => 'nullable|string',
                    ],
                    'theme_background' => [
                        'label' => lang('igniter.main::default.theme_background_label'),
                        'rules' => 'required|string',
                    ],
                    'social' => [
                        'type' => 'repeater',
                        'commentAbove' => 'Add full URL for your social network profiles',
                        'form' => [
                            'fields' => [
                                'class' => [
                                    'label' => 'Icon css class',
                                    'type' => 'text',
                                    'rules' => 'required',
                                    'default' => 'fab fa-facebook',
                                ],
                            ],
                        ],
                    ],
                ];
            }
        };
    }
}
