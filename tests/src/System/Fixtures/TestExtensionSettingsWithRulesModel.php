<?php

namespace Igniter\Tests\System\Fixtures;

use Igniter\System\Actions\SettingsModel;

class TestExtensionSettingsWithRulesModel extends \Igniter\Flame\Database\Model
{
    public array $implement = [SettingsModel::class];

    public $settingsCode = 'test_extension_settings';

    public $settingsFieldsConfig = [
        'form' => [
            'fields' => [
                'field' => [
                    'label' => 'Field',
                    'type' => 'text',
                ],
            ],
            'rules' => [
                'field' => 'required',
            ],
        ],
    ];
}
