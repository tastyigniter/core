<?php

namespace Igniter\Tests\System\Traits;

use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Support\Facades\File;
use Igniter\System\Traits\ConfigMaker;

it('loads config from array', function() {
    $configMaker = new class
    {
        use ConfigMaker;
    };

    $config = $configMaker->loadConfig(['key' => 'value']);
    expect($config)->toBe(['key' => 'value']);
});

it('loads config from file', function() {
    File::shouldReceive('symbolizePath')->with('config.php')->andReturn('config.php');
    File::shouldReceive('symbolizePath')->with('/path/to')->andReturn('/path/to');
    File::shouldReceive('isFile')->with('/path/to/config.php')->andReturn(true);
    File::shouldReceive('getRequire')->with('/path/to/config.php')->andReturn(['key' => 'value']);
    File::shouldReceive('isLocalPath')->andReturnFalse();
    $configMaker = new class
    {
        use ConfigMaker;
    };
    $configMaker->configPath = ['/path/to'];

    expect($configMaker->loadConfig('config'))->toBe(['key' => 'value']);
});

it('throws exception if config file not found', function() {
    File::shouldReceive('symbolizePath')->with('config.php')->andReturn('config.php');
    File::shouldReceive('symbolizePath')->with('/path/to')->andReturn('/path/to');
    File::shouldReceive('isFile')->with('/path/to/config.php')->andReturn(false);
    File::shouldReceive('isFile')->with('config.php')->andReturn(false);
    File::shouldReceive('isLocalPath')->andReturnFalse();
    $configMaker = new class
    {
        use ConfigMaker;
    };

    expect(fn() => $configMaker->loadConfig('config'))->toThrow(SystemException::class);
});

it('throws exception if required config key is missing', function() {
    $configMaker = new class
    {
        use ConfigMaker;
    };

    expect(fn() => $configMaker->loadConfig(['key' => 'value'], ['missingKey']))->toThrow(SystemException::class);
});

it('merges two config arrays', function() {
    $configMaker = new class
    {
        use ConfigMaker;
    };

    $mergedConfig = $configMaker->mergeConfig(['key1' => 'value1'], ['key2' => 'value2']);
    expect($mergedConfig)->toBe(['key1' => 'value1', 'key2' => 'value2']);
});

it('returns local path is file is local', function() {
    File::shouldReceive('isLocalPath')->andReturnTrue();
    File::shouldReceive('symbolizePath')->with('config.php')->andReturn('config.php');
    $configMaker = new class
    {
        use ConfigMaker;
    };

    expect($configMaker->getConfigPath('config.php'))->toBe('config.php');
});

it('returns full path if file name starts with ~', function() {
    File::shouldReceive('isLocalPath')->andReturnFalse();
    File::shouldReceive('symbolizePath')->with('~/config.php')->andReturn('/full/path/config.php');
    $configMaker = new class
    {
        use ConfigMaker;
    };

    expect($configMaker->getConfigPath('~/config.php'))->toBe('/full/path/config.php');
});

it('returns file path from config path array', function() {
    File::shouldReceive('isLocalPath')->andReturnFalse();
    File::shouldReceive('symbolizePath')->with('config.php')->andReturn('config.php');
    File::shouldReceive('symbolizePath')->with('/path/to')->andReturn('/path/to');
    File::shouldReceive('isFile')->with('/path/to/config.php')->andReturn(true);
    $configMaker = new class
    {
        use ConfigMaker;
    };
    $configMaker->configPath = ['/path/to'];

    expect($configMaker->getConfigPath('config.php'))->toBe('/path/to/config.php');
});

it('returns original file name if file not found in config path', function() {
    File::shouldReceive('isLocalPath')->andReturnFalse();
    File::shouldReceive('symbolizePath')->with('config.php')->andReturn('config.php');
    File::shouldReceive('symbolizePath')->with('/path/to')->andReturn('/path/to');
    File::shouldReceive('isFile')->with('/path/to/config.php')->andReturn(false);
    $configMaker = new class
    {
        use ConfigMaker;
    };
    $configMaker->configPath = ['/path/to'];

    expect($configMaker->getConfigPath('config.php'))->toBe('config.php');
});

it('makes config from object correctly', function() {
    $configMaker = new class
    {
        use ConfigMaker;
    };

    expect($configMaker->makeConfig((object)['key' => 'value']))->toBe(['key' => 'value']);
});
