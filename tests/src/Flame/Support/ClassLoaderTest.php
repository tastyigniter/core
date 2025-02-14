<?php

namespace Igniter\Tests\Flame\Support;

use Igniter\Flame\Filesystem\Filesystem;
use Igniter\Flame\Support\ClassLoader;
use Igniter\Flame\Support\Facades\File;

it('registers and unregisters the class loader with SPL autoloader stack', function() {
    $loader = resolve(ClassLoader::class);
    $loader->register();
    $loader->unregister();

    $files = mock(Filesystem::class);
    $loader = new ClassLoader($files, '/base/path', '/manifest/path');
    $loader->unregister();
    expect($loader->unregister())->toBeNull();

    $loader->manifest = [];
    expect($loader->register())->toBeNull();

    $files = mock(Filesystem::class);
    $files->shouldReceive('exists')->andReturnTrue();
    $files->shouldReceive('getRequire')->andReturn('');
    $loader = new ClassLoader($files, '/base/path', '/manifest/path');
    $loader->register();

    $files = mock(Filesystem::class);
    $files->shouldReceive('exists')->andReturnFalse();
    $loader = new ClassLoader($files, '/base/path', '/manifest/path');
    $loader->register();
    expect($loader->manifest)->toBe([]);

    $files = mock(Filesystem::class);
    $files->shouldReceive('exists')->andReturnTrue();
    $files->shouldReceive('getRequire')->andThrow(new \Exception('Error'));
    $loader = new ClassLoader($files, '/base/path', '/manifest/path');
    $loader->register();
});

it('builds correctly', function() {
    $files = mock(Filesystem::class);
    $files->shouldReceive('dirname')->andReturn('/manifest/path');
    $loader = new ClassLoader($files, '/base/path', '/manifest/path');
    $loader->manifest = [];
    $reflection = new \ReflectionClass($loader);
    $property = $reflection->getProperty('manifestIsDirty');
    $property->setAccessible(true);
    $property->setValue($loader, true);

    expect(fn() => $loader->build())
        ->toThrow(\RuntimeException::class, 'The /manifest/path directory must be present and writable.');

    $loader = resolve(ClassLoader::class);
    $reflection = new \ReflectionClass($loader);
    $property = $reflection->getProperty('manifestIsDirty');
    $property->setAccessible(true);
    $property->setValue($loader, true);
    $loader->build();
});

it('loads a class from the manifest', function() {
    mkdir(base_path('extensions/custom/controller/Http/Controllers'), 0755, true);
    file_put_contents(base_path($path = 'extensions/custom/controller/Http/Controllers/TestController.php'), '<?php namespace Custom\\Controller\\Http\\Controllers; class TestController {}');

    $loader = resolve(ClassLoader::class);
    $loader->manifest['Custom\\Controller\\Http\\Controllers\\TestController'] = $path;
    $loader->addNamespaceAliases(['Custom\\Controller\\Http\\Controllers' => 'Custom\\Controller\\Controllers']);
    $result = $loader->load('Custom\\Controller\\Http\\Controllers\\TestController');
    $loader->build();

    expect($result)->toBeTrue();
})->after(function() {
    rescue(fn() => File::deleteDirectory(base_path('extensions/custom/controller')));
});

it('does not load a class if not in the manifest', function() {
    mkdir(base_path('extensions/TestNamespaceAlias'), 0755, true);
    file_put_contents(base_path('extensions/TestNamespaceAlias/TestClass.php'), '<?php namespace TestNamespaceAlias; class TestClass {}');

    $loader = resolve(ClassLoader::class);
    $loader->addNamespaceAliases(['TestNamespaceAlias' => 'TestNamespace']);
    $loader->register();
    expect($loader->load('TestNamespace\\TestClass'))->toBeTrue()
        ->and($loader->load('TestNamespace\\TestClass'))->toBeTrue();
})->after(function() {
    rescue(fn() => File::deleteDirectory(base_path('extensions/TestNamespaceAlias')));
});

it('loads reverse class if not in the manifest', function() {
    mkdir(base_path('extensions/TestNamespaceAlias'), 0755, true);
    file_put_contents(base_path('extensions/TestNamespaceAlias/TestReverseClass.php'), '<?php namespace TestNamespaceAlias; class TestReverseClass {}');

    $loader = resolve(ClassLoader::class);
    $loader->addNamespaceAliases(['TestNamespaceAlias' => 'TestNamespace']);
    $loader->register();
    expect($loader->load('\\TestNamespaceAlias\\TestReverseClass'))->toBeTrue();

    $loader = resolve(ClassLoader::class);
    $loader->addAliases(['\\TestNamespace\\TestReverseClass' => '\\TestNamespaceAlias\\TestReverseClass']);
    $loader->getReverseAlias('\\TestNamespace\\TestReverseClass');
})->after(function() {
    rescue(fn() => File::deleteDirectory(base_path('extensions/TestNamespaceAlias')));
});

it('adds directories to the class loader', function() {
    $files = mock(Filesystem::class);
    $loader = new ClassLoader($files, '/base/path', '/manifest/path');
    $loader->addDirectories(['path/to/directory']);
    expect($loader->getDirectories())->toContain('path/to/directory');
});

it('removes directories from the class loader', function() {
    $files = mock(Filesystem::class);
    $loader = new ClassLoader($files, '/base/path', '/manifest/path');
    $loader->addDirectories(['path/to/directory']);
    $loader->removeDirectories(['path/to/directory']);
    $loader->removeDirectories();
    expect($loader->getDirectories())->not->toContain('path/to/directory');
});

it('adds aliases to the class loader', function() {
    $files = mock(Filesystem::class);
    $loader = new ClassLoader($files, '/base/path', '/manifest/path');
    $loader->addAliases(['OriginalClass' => 'AliasClass']);
    expect($loader->getAlias('AliasClass'))->toBe('OriginalClass');
});

it('adds namespace aliases to the class loader', function() {
    $files = mock(Filesystem::class);
    $loader = new ClassLoader($files, '/base/path', '/manifest/path');
    $loader->addNamespaceAliases(['Original\\Namespace' => 'Alias\\Namespace']);
    expect($loader->getAlias('Alias\\Namespace\\Class'))->toBe('Original\\Namespace\\Class')
        ->and($loader->getNamespaceAliases('Original\\Namespace'))->toBe(['Alias\\Namespace'])
        ->and($loader->getReverseAlias('Original\\Namespace'))->toBe('Alias\\Namespace');
});
