<?php

namespace Igniter\Tests\System\Classes;

use Igniter\Flame\Composer\Manager as ComposerManager;
use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Support\Facades\File;
use Igniter\System\Classes\BaseExtension;
use Igniter\System\Classes\ExtensionManager;
use Igniter\System\Classes\PackageManifest;
use Igniter\System\Classes\UpdateManager;
use Igniter\System\Models\Extension;
use LogicException;
use ZipArchive;

it('returns correct path for extension with folder', function() {
    $manager = resolve(ExtensionManager::class);

    expect($manager->path('igniter.user', 'subfolder'))->toEndWith('/tastyigniter/ti-ext-user/subfolder')
        ->and($manager->path('test-extension'))->toBe('/');
});

it('returns list of all extensions', function() {
    $manager = resolve(ExtensionManager::class);

    $extensions = $manager->listExtensions();
    expect($extensions)->toContain('igniter.user');
});

it('returns extension lookup folders with added directory', function() {
    File::shouldReceive('isDirectory')->andReturnTrue();
    File::shouldReceive('glob')->andReturn([
        '/path/to/extensions/igniter/user/composer.json',
        '/path/to/extensions/igniter/blog/composer.json',
    ]);
    $manager = resolve(ExtensionManager::class);
    ExtensionManager::addDirectory('/path/to/extensions');

    $folders = $manager->folders();
    expect($folders)->toContain(
        '/path/to/extensions/igniter/user',
        '/path/to/extensions/igniter/blog',
    )
        ->and($manager->namespaces())->toHaveKeys(['igniter\user']);
});

it('loads extensions correctly', function() {
    $manager = resolve(ExtensionManager::class);
    $manager->loadExtensions();

    expect($manager->getExtensions())->toBeArray();
});

it('throws exception if extension namespace is missing', function() {
    $manager = resolve(ExtensionManager::class);

    expect(fn() => $manager->loadExtension('/invalid/path'))->toThrow(SystemException::class);
});

it('returns existing extension if already loaded', function() {
    $manager = resolve(ExtensionManager::class);
    $path = __DIR__.'/../../Fixtures/Extension';

    expect($manager->loadExtension($path))->toBe($manager->loadExtension($path));
});

it('loads extension and sets PSR-4 autoloading', function() {
    $composerManager = mock(ComposerManager::class)->makePartial();
    $composerManager->shouldReceive('getLoader')->andReturnSelf();
    $composerManager->shouldReceive('getPrefixesPsr4')->andReturn([]);
    $composerManager->shouldReceive('setPsr4')->andReturnTrue();
    $manager = resolve(ExtensionManager::class, [
        'composerManager' => $composerManager,
        'packageManifest' => resolve(PackageManifest::class),
    ]);

    $path = __DIR__.'/../../Fixtures/Extension';
    expect($manager->loadExtension($path))->toBeInstanceOf(BaseExtension::class);
});

it('throws exception when extension config validation fails', function() {
    File::shouldReceive('isDirectory')->andReturnTrue();
    File::shouldReceive('exists')->andReturnFalse();
    File::shouldReceive('glob')->andReturn([
        '/path/to/extensions/igniter/user/composer.json',
        '/path/to/extensions/igniter/blog/composer.json',
    ]);
    $manager = resolve(ExtensionManager::class);
    ExtensionManager::addDirectory('/path/to/extensions');

    expect(fn() => $manager->loadExtensions())
        ->toThrow(SystemException::class);
});

it('returns null for invalid extension name', function() {
    $manager = resolve(ExtensionManager::class);
    $name = $manager->checkName('invalid name');
    expect($name)->toBeNull();
});

it('returns identifier from namespace', function() {
    $manager = resolve(ExtensionManager::class);
    $identifier = $manager->getIdentifier('Test\\Namespace');
    expect($identifier)->toBe('test.namespace');
});

it('returns correct extension path', function() {
    $manager = resolve(ExtensionManager::class);

    $path = $manager->getExtensionPath('igniter.user', '/subfolder');

    expect($path)->toEndWith('/tastyigniter/ti-ext-user/subfolder')
        ->and($manager->getNamePath('test.extension'))->toEndWith('test/extension')
        ->and($manager->hasVendor('test.extension'))->toBeFalse();
});

it('updates installed extensions correctly', function() {
    $manager = resolve(ExtensionManager::class);

    $result = $manager->updateInstalledExtensions('igniter.user', false);
    expect($result)->toBeTrue()
        ->and($manager->isDisabled('igniter.user'))->toBeTrue();
});

it('removes extension correctly', function() {
    File::shouldReceive('isDirectory')->andReturnTrue();
    File::shouldReceive('deleteDirectory')->andReturnTrue();
    File::shouldReceive('directories')->andReturn([]);

    $manager = resolve(ExtensionManager::class);

    expect($manager->removeExtension('igniter.user'))->toBeTrue();
});

it('throws exception if extension directory is not found', function() {
    $manager = resolve(ExtensionManager::class);

    expect(fn() => $manager->resolveExtension('test.identifier', '/invalid/path', 'Test\\Namespace\\Extension'))
        ->toThrow(SystemException::class, 'Extension directory not found: /invalid/path');
});

it('throws exception if extension class is missing', function() {
    $manager = resolve(ExtensionManager::class);

    expect(fn() => $manager->resolveExtension('test.identifier', __DIR__, null))
        ->toThrow(LogicException::class, "Missing Extension class '' in 'test.identifier', create the Extension class to override extensionMeta() method.");
});

it('throws exception if extension class does not exist', function() {
    $manager = resolve(ExtensionManager::class);

    expect(fn() => $manager->resolveExtension('test.identifier', __DIR__, 'NonExistentClass'))
        ->toThrow(LogicException::class, "Missing Extension class 'NonExistentClass' in 'test.identifier', create the Extension class to override extensionMeta() method.");
});

it('throws exception if extension class does not extend BaseExtension', function() {
    $manager = resolve(ExtensionManager::class);

    expect(fn() => $manager->resolveExtension('test.identifier', __DIR__, \stdClass::class))
        ->toThrow(LogicException::class, "Extension class 'stdClass' must extend 'Igniter\System\Classes\BaseExtension'.");
});

it('checks if extension is required', function() {
    $manager = resolve(ExtensionManager::class);

    expect($manager->isRequired('test.extension'))->toBeFalse()
        ->and($manager->isRequired('igniter.api'))->toBeTrue()
        ->and($manager->isRequired('igniter.automation'))->toBeTrue()
        ->and($manager->isRequired('igniter.broadcast'))->toBeTrue()
        ->and($manager->isRequired('igniter.cart'))->toBeTrue()
        ->and($manager->isRequired('igniter.local'))->toBeTrue()
        ->and($manager->isRequired('igniter.payregister'))->toBeTrue()
        ->and($manager->isRequired('igniter.reservation'))->toBeTrue()
        ->and($manager->isRequired('igniter.user'))->toBeTrue()
        ->and($manager->isRequired('igniter.orange'))->toBeTrue();
});

it('returns cached registration method values if available', function() {
    $manager = resolve(ExtensionManager::class);

    expect($manager->getRegistrationMethodValues('registerPermissions'))
        ->toBe($manager->getRegistrationMethodValues('registerPermissions'));
});

it('extracts extension zip folder correctly', function() {
    $manager = resolve(ExtensionManager::class);
    $zipPath = '/path/to/valid/extension.zip';
    $zip = mock(ZipArchive::class);
    $zip->shouldReceive('open')->with($zipPath)->andReturnTrue();
    $zip->shouldReceive('getNameIndex')->with(0)->andReturn('/path/to/valid/extension/');
    $zip->shouldReceive('locateName')->with('/path/to/valid/extension/Extension.php')->andReturnTrue();
    $zip->shouldReceive('getFromName')->andReturn(json_encode(['code' => 'valid.extension']));
    $zip->shouldReceive('extractTo')->withArgs(fn($path) => ends_with($path, '/extensions/valid/extension'))->andReturnTrue();
    $zip->shouldReceive('close')->andReturnTrue();
    app()->instance(ZipArchive::class, $zip);
    File::shouldReceive('exists')->with('/path/to/valid/extension/extension.json')->andReturn(false);
    File::shouldReceive('exists')->with('/path/to/valid/extension/composer.json')->andReturn(true);

    $extensionCode = $manager->extractExtension($zipPath);
    expect($extensionCode)->toBe('valid.extension');
});

it('extractExtension throws exception if extension name has spaces', function() {
    $manager = resolve(ExtensionManager::class);
    $zipPath = '/path/to/invalid/extension.zip';
    $zip = mock(ZipArchive::class);
    $zip->shouldReceive('open')->with($zipPath)->andReturnTrue();
    $zip->shouldReceive('getNameIndex')->with(0)->andReturn('/path/to/invalid/extens ion/');
    app()->instance(ZipArchive::class, $zip);
    File::shouldReceive('exists')->with('/path/to/invalid/extension/extension.json')->andReturn(false);
    File::shouldReceive('exists')->with('/path/to/invalid/extension/composer.json')->andReturn(true);
    File::shouldReceive('isDirectory')->with('/path/to/invalid/extension')->andReturn(true);
    File::shouldReceive('deleteDirectory')->with('/path/to/invalid/extension')->andReturn(true);

    expect(fn() => $manager->extractExtension($zipPath))->toThrow(SystemException::class, 'Extension name can not have spaces.');
});

it('extractExtension throws exception if extension registration class is not found', function() {
    $manager = resolve(ExtensionManager::class);
    $zipPath = '/path/to/invalid/extension.zip';
    $zip = mock(ZipArchive::class);
    $zip->shouldReceive('open')->with($zipPath)->andReturnTrue();
    $zip->shouldReceive('getNameIndex')->with(0)->andReturn('/path/to/invalid/extension/');
    $zip->shouldReceive('locateName')->with('/path/to/invalid/extension/Extension.php')->andReturnFalse();
    app()->instance(ZipArchive::class, $zip);
    File::shouldReceive('exists')->with('/path/to/invalid/extension/extension.json')->andReturn(false);
    File::shouldReceive('exists')->with('/path/to/invalid/extension/composer.json')->andReturn(true);
    File::shouldReceive('isDirectory')->with('/path/to/invalid/extension')->andReturn(true);
    File::shouldReceive('deleteDirectory')->with('/path/to/invalid/extension')->andReturn(true);

    expect(fn() => $manager->extractExtension($zipPath))->toThrow(SystemException::class, 'Extension registration class was not found.');
});

it('extractExtension throws exception if extension.json file is found', function() {
    $manager = resolve(ExtensionManager::class);
    $zipPath = '/path/to/invalid/extension.zip';
    $zip = mock(ZipArchive::class);
    $zip->shouldReceive('open')->with($zipPath)->andReturnTrue();
    $zip->shouldReceive('getNameIndex')->with(0)->andReturn('/path/to/invalid/extension/');
    $zip->shouldReceive('locateName')->with('/path/to/invalid/extension/Extension.php')->andReturnTrue();
    app()->instance(ZipArchive::class, $zip);
    File::shouldReceive('exists')->with('/path/to/invalid/extension/extension.json')->andReturn(true);

    expect(fn() => $manager->extractExtension($zipPath))
        ->toThrow(SystemException::class, 'extension.json files are no longer supported, please convert to composer.json: /path/to/invalid/extension/extension.json');
});

it('extractExtension throws exception if composer.json file is invalid', function() {
    $manager = resolve(ExtensionManager::class);
    $zipPath = '/path/to/invalid/extension.zip';
    $zip = mock(ZipArchive::class);
    $zip->shouldReceive('open')->with($zipPath)->andReturnTrue();
    $zip->shouldReceive('getNameIndex')->with(0)->andReturn('/path/to/invalid/extension/');
    $zip->shouldReceive('locateName')->with('/path/to/invalid/extension/Extension.php')->andReturnTrue();
    $zip->shouldReceive('getFromName')->andReturn(json_encode([]));
    app()->instance(ZipArchive::class, $zip);
    File::shouldReceive('exists')->with('/path/to/invalid/extension/extension.json')->andReturn(false);
    File::shouldReceive('exists')->with('/path/to/invalid/extension/composer.json')->andReturn(true);

    expect(fn() => $manager->extractExtension($zipPath))
        ->toThrow(SystemException::class, lang('igniter::system.extensions.error_config_no_found'));
});

it('installs extension successfully', function() {
    $manager = resolve(ExtensionManager::class);
    $updateManager = mock(UpdateManager::class);
    $updateManager->shouldReceive('migrateExtension')->with('igniter.user');
    app()->instance(UpdateManager::class, $updateManager);

    expect($manager->installExtension('igniter.user'))->toBeTrue();
});

it('installExtension returns false if extension class is not applied', function() {
    $manager = resolve(ExtensionManager::class);

    expect($manager->installExtension('test.extension'))->toBeFalse();
});

it('uninstalls extension and purges data', function() {
    $manager = resolve(ExtensionManager::class);
    $updateManager = mock(UpdateManager::class);
    $updateManager->shouldReceive('purgeExtension')->with('test.extension');
    app()->instance(UpdateManager::class, $updateManager);

    expect($manager->uninstallExtension('test.extension', true))->toBeTrue();
});

it('uninstalls extension without purging data', function() {
    $manager = resolve(ExtensionManager::class);

    expect($manager->uninstallExtension('test.extension'))->toBeTrue();
});

it('deletes extension and purges data', function() {
    $manager = resolve(ExtensionManager::class);
    Extension::create(['name' => 'test.extension', 'status' => 1]);
    $updateManager = mock(UpdateManager::class);
    $updateManager->shouldReceive('purgeExtension')->with('test.extension');
    app()->instance(UpdateManager::class, $updateManager);
    $composerManager = mock(ComposerManager::class);
    $composerManager->shouldReceive('getPackageName')->with('test.extension')->andReturnNull()->once();
    app()->instance(ComposerManager::class, $composerManager);
    File::shouldReceive('isDirectory')->andReturnTrue();
    File::shouldReceive('deleteDirectory')->andReturnTrue();
    File::shouldReceive('directories')->andReturn([]);

    $manager->deleteExtension('test.extension');
});

it('deletes extension without purging data', function() {
    $manager = resolve(ExtensionManager::class);
    $composerManager = mock(ComposerManager::class);
    $composerManager->shouldReceive('getPackageName')->with('test.extension')->andReturnNull()->once();
    app()->instance(ComposerManager::class, $composerManager);
    File::shouldReceive('isDirectory')->andReturnTrue();
    File::shouldReceive('deleteDirectory')->andReturnTrue();
    File::shouldReceive('directories')->andReturn([]);

    $manager->deleteExtension('test.extension', false);
});

it('uninstalls composer package if package name is found', function() {
    $manager = resolve(ExtensionManager::class);
    $composerManager = mock(ComposerManager::class);
    $composerManager->shouldReceive('getPackageName')->with('test.extension')->andReturn('test/extension');
    $composerManager->shouldReceive('uninstall')->with(['test/extension' => false])->andReturnTrue()->once();
    app()->instance(ComposerManager::class, $composerManager);
    File::shouldReceive('isDirectory')->andReturnTrue();
    File::shouldReceive('deleteDirectory')->andReturnTrue();
    File::shouldReceive('directories')->andReturn([]);

    $manager->deleteExtension('test.extension', false);
});
