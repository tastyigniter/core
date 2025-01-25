<?php

namespace Igniter\Tests\System\Fixtures;

use Igniter\System\Actions\SettingsModel;

class TestExtensionSettingsModel extends \Igniter\Flame\Database\Model
{
    public array $implement = [SettingsModel::class];

    public $settingsCode = 'test_extension_settings';

    public $settingsFieldsConfig = 'test_settings';
}
