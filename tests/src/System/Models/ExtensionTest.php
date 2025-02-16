<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Models\Concerns;

use Igniter\Flame\Support\Facades\File;
use Igniter\Main\Classes\Theme;
use Igniter\Main\Classes\ThemeManager;
use Igniter\System\Classes\BaseExtension;
use Igniter\System\Classes\ExtensionManager;
use Igniter\System\Classes\PackageManifest;
use Igniter\System\Models\Extension;

it('onboardingIsComplete returns false when there is no active theme', function() {
    $themeManager = mock(ThemeManager::class);
    app()->instance(ThemeManager::class, $themeManager);
    $themeManager->shouldReceive('getActiveTheme')->andReturnNull();

    expect(Extension::onboardingIsComplete())->toBeFalse();
});

it('onboardingIsComplete returns false when a required extension is missing', function() {
    $theme = new Theme('/path/to/theme', [
        'require' => ['TestExtension' => '*'],
    ]);
    $themeManager = mock(ThemeManager::class);
    app()->instance(ThemeManager::class, $themeManager);
    $themeManager->shouldReceive('getActiveTheme')->andReturn($theme);
    $extensionManager = mock(ExtensionManager::class);
    app()->instance(ExtensionManager::class, $extensionManager);
    $extensionManager->shouldReceive('findExtension')->with('TestExtension')->andReturnNull();

    expect(Extension::onboardingIsComplete())->toBeFalse();
});

it('onboardingIsComplete returns false when a required extension is disabled', function() {
    $theme = new Theme('/path/to/theme', [
        'require' => ['TestExtension' => '*'],
    ]);
    $extension = new class(app()) extends BaseExtension
    {
        public bool $disabled = true;
    };
    $themeManager = mock(ThemeManager::class);
    app()->instance(ThemeManager::class, $themeManager);
    $themeManager->shouldReceive('getActiveTheme')->andReturn($theme);
    $extensionManager = mock(ExtensionManager::class);
    app()->instance(ExtensionManager::class, $extensionManager);
    $extensionManager->shouldReceive('findExtension')->with('TestExtension')->andReturn($extension);

    expect(Extension::onboardingIsComplete())->toBeFalse();
});

it('onboardingIsComplete returns true when all required extensions are enabled', function() {
    $theme = new Theme('/path/to/theme', [
        'require' => ['TestExtension' => '*'],
    ]);
    $extension = new class(app()) extends BaseExtension {};
    $themeManager = mock(ThemeManager::class);
    app()->instance(ThemeManager::class, $themeManager);
    $themeManager->shouldReceive('getActiveTheme')->andReturn($theme);
    $extensionManager = mock(ExtensionManager::class);
    app()->instance(ExtensionManager::class, $extensionManager);
    $extensionManager->shouldReceive('findExtension')->with('TestExtension')->andReturn($extension);

    expect(Extension::onboardingIsComplete())->toBeTrue();
});

it('returns default version when version attribute is null', function() {
    $extension = new Extension(['version' => null]);

    $version = $extension->version;

    expect($version)->toBe('0.1.0');
});

it('returns correct title from meta attribute', function() {
    $extension = new Extension;
    $extension->class = new class(app()) extends BaseExtension
    {
        public function extensionMeta(): array
        {
            return [
                'name' => 'Test Extension',
                'author' => 'Igniter Labs',
                'description' => 'A test extension',
                'icon' => 'fa-cog',
            ];
        }
    };

    expect($extension->title)->toBe('Test Extension');
});

it('returns correct status when extension is enabled', function() {
    $extension = new Extension;
    $extension->class = new class(app()) extends BaseExtension
    {
        public bool $disabled = false;
    };

    expect($extension->status)->toBeTrue();
});

it('returns correct status when extension is disabled', function() {
    $extension = new Extension;
    $extension->class = new class(app()) extends BaseExtension
    {
        public bool $disabled = true;
    };

    expect($extension->status)->toBeFalse();
});

it('returns correct description from meta attribute', function() {
    $extension = new Extension;
    $extension->class = new class(app()) extends BaseExtension
    {
        public function extensionMeta(): array
        {
            return ['description' => 'Test Description'];
        }
    };

    expect($extension->description)->toBe('Test Description');
});

it('returns correct required from extension', function() {
    $extensionManager = mock(ExtensionManager::class);
    app()->instance(ExtensionManager::class, $extensionManager);
    $extensionManager->shouldReceive('isRequired')->with('test_extension')->andReturnTrue();

    $extension = new Extension(['name' => 'test_extension']);

    expect($extension->required)->toBeTrue();
});

it('returns correct icon from meta attribute', function() {
    $extension = new Extension;
    $extension->class = new class(app()) extends BaseExtension
    {
        public function extensionMeta(): array
        {
            return [
                'icon' => 'fa-cog',
            ];
        }
    };

    $icon = $extension->icon;

    expect($icon['class'])->toBe('fa fa-cog')
        ->and($icon['image'])->toBeNull()
        ->and($icon['backgroundImage'])->toBeNull();
});

it('returns correct icon image from meta attribute', function() {
    $extensionManager = mock(ExtensionManager::class);
    app()->instance(ExtensionManager::class, $extensionManager);
    $extensionManager->shouldReceive('path')->with('test_extension', 'image.png')->andReturn('/path/to/image.png');
    File::shouldReceive('exists')->andReturn(true);
    File::shouldReceive('get')->with('/path/to/image.png')->andReturn('image content');

    $extension = new Extension(['name' => 'test_extension']);
    $extension->class = new class(app()) extends BaseExtension
    {
        public function extensionMeta(): array
        {
            return [
                'icon' => [
                    'image' => 'image.png',
                ],
            ];
        }
    };

    $icon = $extension->icon;

    expect($icon['class'])->toBe('fa')
        ->and($icon['image'])->toBe('image.png')
        ->and($icon['backgroundImage'])->toBe([
            'image/png', base64_encode('image content'),
        ])
        ->and($icon['styles'])->toBe("background-image:url('data:image/png;base64,".base64_encode('image content')."');");
});

it('throws exception when icon mime type is invalid', function() {
    $extensionManager = mock(ExtensionManager::class);
    app()->instance(ExtensionManager::class, $extensionManager);
    $extensionManager->shouldReceive('path')->with('test_extension', 'image.jpg')->andReturn('/path/to/image.jpg');
    File::shouldReceive('exists')->andReturn(true);
    File::shouldReceive('get')->with('/path/to/image.jpg')->andReturn('image content');

    $extension = new Extension(['name' => 'test_extension']);
    $extension->class = new class(app()) extends BaseExtension
    {
        public function extensionMeta(): array
        {
            return [
                'icon' => [
                    'image' => 'image.jpg',
                ],
            ];
        }
    };

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid extension icon file type in: test_extension. Only SVG and PNG images are supported');

    $extension->icon;
});

it('returns undefined description when meta description is not set', function() {
    $extension = new Extension;
    $extension->class = new class(app()) extends BaseExtension
    {
        public function extensionMeta(): array
        {
            return [];
        }
    };

    expect($extension->description)->toBe('Undefined extension description');
});

it('returns correct readme content when readme file exists', function() {
    $extensionManager = mock(ExtensionManager::class);
    app()->instance(ExtensionManager::class, $extensionManager);
    $extensionManager->shouldReceive('path')->with('test_extension', 'readme.md')->andReturn('/path/to/readme.md');
    File::shouldReceive('existsInsensitive')->andReturn(true);
    File::shouldReceive('get')->andReturn('Test **Readme**');

    $extension = new Extension(['name' => 'test_extension']);

    expect($extension->readme)->toBe("<p>Test <strong>Readme</strong></p>\n");
});

it('returns null readme content when readme file does not exist', function() {
    $extensionManager = mock(ExtensionManager::class);
    app()->instance(ExtensionManager::class, $extensionManager);
    $extensionManager->shouldReceive('path')->with('test_extension', 'readme.md')->andReturn('/path/to/readme.md');
    File::shouldReceive('existsInsensitive')->andReturn(false);

    $extension = new Extension(['name' => 'test_extension']);

    expect($extension->readme)->toBeNull();
});

it('applies extension class on fetch', function() {
    $extensionClass = new class(app()) extends BaseExtension
    {
        public function extensionMeta(): array
        {
            return ['name' => 'Test Extension'];
        }
    };
    $extensionManager = mock(ExtensionManager::class);
    app()->instance(ExtensionManager::class, $extensionManager);
    $extensionManager->shouldReceive('findExtension')->with('test_extension')->andReturn($extensionClass);

    Extension::flushEventListeners();
    Extension::create(['name' => 'test_extension']);
    $extension = Extension::firstWhere('name', 'test_extension');

    expect($extension->getExtensionObject())->toBe($extensionClass);
});

it('does not apply extension class when extension is not found', function() {
    $extensionManager = mock(ExtensionManager::class);
    app()->instance(ExtensionManager::class, $extensionManager);
    $extensionManager->shouldReceive('findExtension')->with('test_extension')->andReturnNull();

    Extension::create(['name' => 'test_extension']);
    $extension = Extension::firstWhere('name', 'test_extension');

    expect($extension->class)->toBeNull();
});

it('syncs available extensions from filesystem', function() {
    $packageManifest = mock(PackageManifest::class);
    app()->instance(PackageManifest::class, $packageManifest);
    $packageManifest->shouldReceive('getVersion')->andReturn('1.0.0');
    $extensionManager = mock(ExtensionManager::class);
    app()->instance(ExtensionManager::class, $extensionManager);
    $extensionManager->shouldReceive('namespaces')->andReturn(['test_extension' => '/path/to/test_extension']);
    $extensionManager->shouldReceive('getIdentifier')->with('test_extension')->andReturn('test.extension');
    $extensionManager->shouldReceive('findExtension')->with('test.extension')->andReturn(mock(BaseExtension::class));

    Extension::syncAll();

    expect(Extension::firstWhere('name', 'test.extension'))->not->toBeNull();
});

it('skips syncing extension not found in filesystem', function() {
    $extensionManager = mock(ExtensionManager::class);
    app()->instance(ExtensionManager::class, $extensionManager);
    $extensionManager->shouldReceive('namespaces')->andReturn(['test_extension' => '/path/to/test_extension']);
    $extensionManager->shouldReceive('getIdentifier')->with('test_extension')->andReturn('test.extension');
    $extensionManager->shouldReceive('findExtension')->with('test.extension')->andReturnNull();

    Extension::syncAll();

    expect(Extension::firstWhere('name', 'test.extension'))->toBeNull();
});

it('configures extension model correctly', function() {
    $extension = new Extension;

    expect(Extension::ICON_MIMETYPES)->toEqual([
        'png' => 'image/png',
        'svg' => 'image/svg+xml',
    ])
        ->and($extension->getTable())->toBe('extensions')
        ->and($extension->getKeyName())->toBe('extension_id')
        ->and($extension->getFillable())->toEqual(['name', 'version']);
});
