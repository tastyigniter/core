<?php

namespace Igniter\Tests\System\Actions;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Support\Facades\Igniter;
use Igniter\System\Actions\SettingsModel;

beforeEach(function() {
    $this->settingsModel = new class extends Model
    {
        public array $implement = [SettingsModel::class];

        public string $settingsCode = 'test_settings';

        public string $settingsFieldsConfig = 'igniter.tests::/models/test_settings';

        public function getMutatedKeyAttribute()
        {
            return 'mutated_value';
        }
    };
});

it('resets settings to defaults by deleting the record', function() {
    $this->settingsModel->set('key', 'value');

    $this->settingsModel->resetDefault();

    expect($this->settingsModel->get('key'))->toBeNull();
});

it('returns true if model is configured', function() {
    Igniter::shouldReceive('hasDatabase')->andReturn(true);
    $this->settingsModel->create([
        'item' => 'test_settings',
        'data' => ['key' => 'value'],
    ]);

    expect($this->settingsModel->isConfigured())->toBeTrue();
});

it('returns false if database is not available', function() {
    Igniter::shouldReceive('hasDatabase')->andReturn(false);

    expect($this->settingsModel->isConfigured())->toBeFalse();
});

it('sets single & multiple key value pair correctly', function() {
    $this->settingsModel->set('key', 'value');
    $this->settingsModel->set(['key1' => 'value1', 'key2' => 'value2']);

    expect($this->settingsModel->get('key'))->toBe('value')
        ->and($this->settingsModel->get('key1'))->toBe('value1')
        ->and($this->settingsModel->get('key2'))->toBe('value2');
});

it('returns value if key exists', function() {
    $this->settingsModel->set(['key' => 'value']);
    $this->settingsModel->afterModelFetch();

    expect($this->settingsModel->get('key'))->toBe('value');
});

it('returns default value if key does not exist in fieldValues', function() {
    $result = $this->settingsModel->get('nonexistent_key', 'default_value');

    expect($result)->toBe('default_value');
});

it('returns value from model attribute if get mutator exists', function() {
    $result = $this->settingsModel->get('mutated_key');

    expect($result)->toBe('mutated_value');
});

it('loads and returns fieldConfig', function() {
    $this->settingsModel->getFieldConfig(); // test cache for code coverage
    $result = $this->settingsModel->getFieldConfig();

    expect($result)->toBe([
        'toolbar' => [],
        'fields' => [
            'name' => [
                'label' => 'Name',
                'type' => 'text',
                'span' => 'left',
            ],
        ],
    ]);

    SettingsModel::clearInternalCache();
});
