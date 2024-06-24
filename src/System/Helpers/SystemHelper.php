<?php

namespace Igniter\System\Helpers;

use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Support\Facades\File;
use Igniter\System\Classes\ComposerManager;
use Illuminate\Support\Facades\Validator;

class SystemHelper
{
    /**
     * Returns the PHP version, without the distribution info.
     */
    public static function phpVersion(): string
    {
        return PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION;
    }

    /**
     * Returns a PHP extension version, without the distribution info.
     */
    public static function extensionVersion(string $name): string
    {
        $version = phpversion($name);

        return static::normalizeVersion($version);
    }

    /**
     * Removes distribution info from a version
     */
    public static function normalizeVersion(string $version): string
    {
        return preg_replace('/^([^\s~+-]+).*$/', '$1', $version);
    }

    /**
     * Tests whether ini_set() works.
     */
    public static function assertIniSet()
    {
        $oldValue = ini_get('memory_limit');
        $oldBytes = static::phpIniValueInBytes('memory_limit');

        // When the old value is not equal to '-1', add 1MB to the limit set at the moment
        $testBytes = $oldBytes === -1 ? 1024 * 1024 * 442 : $oldBytes + 1024 * 1024;

        $testValue = sprintf('%sM', ceil($testBytes / (1024 * 1024)));
        set_error_handler(function() {});
        $result = ini_set('memory_limit', $testValue);
        $newValue = ini_get('memory_limit');
        ini_set('memory_limit', $oldValue);
        restore_error_handler();

        // ini_set can return false or an empty string depending on your php version / FastCGI.
        // If ini_set has been disabled in php.ini, the value will be null because of our muted error handler
        return
            $result !== false &&
            $result !== '' &&
            $result !== $newValue;
    }

    public static function assertIniMaxExecutionTime(int $int): bool
    {
        $timeLimit = (int)trim(ini_get('max_execution_time'));

        return $timeLimit !== 0 && $timeLimit < 120;
    }

    public static function assertIniMemoryLimit(int $int): bool
    {
        $memoryLimit = static::phpIniValueInBytes('memory_limit');

        return $memoryLimit !== -1 && $memoryLimit < 1024 * 1024 * 256;
    }

    /**
     * Retrieves a bool PHP config setting and normalizes it to an actual bool.
     */
    public static function phpIniValueAsBool(string $var): bool
    {
        $value = trim(ini_get($var));

        return $value === '1' || strtolower($value) === 'on';
    }

    /**
     * Retrieves a disk size PHP config setting and normalizes it into bytes.
     */
    public static function phpIniValueInBytes(string $var): float|int
    {
        $value = trim(ini_get($var));

        return static::phpSizeInBytes($value);
    }

    /**
     * Normalizes a PHP file size into bytes.
     */
    public static function phpSizeInBytes(string $value): float|int
    {
        $unit = strtolower(substr($value, -1, 1));
        $value = (int)$value;

        switch ($unit) {
            case 'g':
                $value *= 1024;
                // no break
            case 'm':
                $value *= 1024;
                // no break
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    public static function replaceInEnv(string $search, string $replace)
    {
        $file = base_path().'/.env';

        file_put_contents(
            $file,
            preg_replace('/^'.$search.'(.*)$/m', $replace, file_get_contents($file))
        );

        putenv($replace);
    }

    public static function extensionConfigFromFile(string $path): array
    {
        throw_if(
            File::exists($manifestFile = $path.'/extension.json'),
            new SystemException("extension.json files are no longer supported, please convert to composer.json: $manifestFile")
        );

        throw_unless(File::exists($path.'/composer.json'), new SystemException(
            "Required extension configuration file not found: $path/composer.json"
        ));

        return resolve(ComposerManager::class)->getExtensionManifest($path);
    }

    public static function extensionValidateConfig(array $config): array
    {
        Validator::make($config, [
            'code' => [
                'required',
                'regex:/^[A-Za-z_-]+(\.?)+[A-Za-z_-]+$/',
                'max:64',
            ],
            'name' => ['required', 'string'],
            'author' => ['string'],
            'description' => ['required', 'string', 'max:255'],
            'icon' => ['sometimes'],
            'icon.class' => ['sometimes', 'string', 'max:30'],
            'icon.color' => ['sometimes', 'string', 'max:30'],
            'icon.image' => ['sometimes', 'string', 'max:30'],
            'icon.backgroundColor' => ['sometimes', 'string', 'max:30'],
            'homepage' => ['sometimes', 'url', 'max:255'],
            'require' => ['sometimes', 'array'],
        ])->validate();

        return $config;
    }
}
