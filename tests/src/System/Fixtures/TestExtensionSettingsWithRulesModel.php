<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Fixtures;

use Igniter\Flame\Database\Model;
use Igniter\System\Actions\SettingsModel;

class TestExtensionSettingsWithRulesModel extends Model
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
