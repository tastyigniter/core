<?php

namespace Igniter\Tests\Main\Classes;

use Igniter\Flame\Exception\FlashException;
use Igniter\Flame\Pagic\Source\ChainFileSource;
use Igniter\Flame\Support\Facades\File;
use Igniter\Flame\Support\Facades\Igniter;
use Igniter\Main\Classes\Theme;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Events\ThemeGetActiveEvent;
use Igniter\Main\Template\Page as PageTemplate;
use Illuminate\Support\Facades\Event;
use RuntimeException;

beforeEach(function() {
    $this->themePath = testThemePath();
});

it('returns the correct theme name', function() {
    $theme = new Theme('/path/to/theme', ['code' => 'themeCode', 'require' => 'requires']);
    expect($theme->getName())->toBe('themeCode');
});

it('returns the correct theme path', function() {
    $theme = new Theme($this->themePath);
    expect($theme->getPath())->toBe($this->themePath)
        ->and($theme->getDirName())->toBe(basename($this->themePath));
});

it('returns the correct source path', function() {
    $theme = new Theme($this->themePath, ['source-path' => '/source']);
    expect($theme->getSourcePath())->toBe($this->themePath.'/source');
});

it('returns the correct meta path', function() {
    File::shouldReceive('localToPublic')->andReturn('/public');
    File::shouldReceive('isDirectory')->andReturn(true, false);
    $theme = new Theme($this->themePath);
    expect($theme->getMetaPath())->toBe($this->themePath.'/_meta');
});

it('returns the correct asset file path', function() {
    File::shouldReceive('localToPublic')->andReturn('/public');
    File::shouldReceive('isDirectory')->andReturn(true, false);
    $theme = new Theme($this->themePath, ['asset-path' => '/assets']);
    expect($theme->getAssetsFilePath())->toBe($this->themePath.'/_meta/assets.json');
});

it('returns the correct asset path', function() {
    $theme = new Theme($this->themePath, ['asset-path' => '/assets']);
    expect($theme->getAssetPath())->toBe($this->themePath.'/assets');
});

it('returns the correct paths to publish', function() {
    File::shouldReceive('localToPublic')->andReturn('/public');
    File::shouldReceive('exists')->andReturn(true);
    $theme = new Theme($this->themePath, ['code' => 'themeCode', 'asset-path' => '/assets']);
    expect($theme->getPathsToPublish())->toBe([$this->themePath.'/assets' => public_path('vendor/themeCode')]);
});

it('returns the correct parent path', function() {
    $parentTheme = new Theme($this->themePath);
    $theme = new Theme(dirname($this->themePath, 2), ['parent' => 'parentTheme']);
    app()->instance(ThemeManager::class, $themeManager = mock(ThemeManager::class));
    $themeManager->shouldReceive('findTheme')->andReturn($parentTheme);
    expect($theme->getParentPath())->toBe($this->themePath)
        ->and($theme->getParentPath())->toBe($this->themePath);
});

it('returns the correct screenshot data', function() {
    File::shouldReceive('localToPublic')->andReturn('/public');
    File::shouldReceive('isFile')->andReturn(true);
    File::shouldReceive('get')->andReturn('file content');
    File::shouldReceive('exists')->andReturn(true);
    $theme = new Theme(dirname($this->themePath, 2), ['parent' => 'parentTheme']);
    app()->instance(ThemeManager::class, $themeManager = mock(ThemeManager::class));
    $themeManager->shouldReceive('findTheme')->andReturn(new Theme($this->themePath));

    $theme->screenshot('screenshot');
    expect($theme->getScreenshotData())->toBe('data:image/png;base64,'.base64_encode('file content'));
});

it('throws exception for invalid screenshot file type', function() {
    File::shouldReceive('localToPublic')->andReturn('/public');
    File::shouldReceive('isFile')->andReturn(true);
    File::shouldReceive('exists')->andReturn(true);
    $theme = new Theme($this->themePath);
    $theme->fillFromConfig();
    $theme->screenshot = '/path/to/theme/screenshot.jpg';
    expect(fn() => $theme->getScreenshotData())->toThrow(FlashException::class);
});

it('returns empty screenshot data when screenshot does not exist', function() {
    File::shouldReceive('localToPublic')->andReturn('/public');
    File::shouldReceive('isFile')->andReturn(true);
    File::shouldReceive('exists')->andReturn(false);
    $theme = new Theme($this->themePath);
    expect($theme->getScreenshotData())->toBe('')
        ->and($theme->getScreenshotData())->toBe('');
});

it('loads theme file if exists', function() {
    $parentTheme = new Theme($this->themePath);
    $theme = new Theme(dirname($this->themePath, 2), ['parent' => 'parentTheme']);
    app()->instance(ThemeManager::class, $themeManager = mock(ThemeManager::class));
    $themeManager->shouldReceive('findTheme')->andReturn($parentTheme);
    File::shouldReceive('exists')->with(dirname($this->themePath, 2).'/theme.php')->andReturn(true, false);
    File::shouldReceive('exists')->with($this->themePath.'/theme.php')->andReturn(true);

    expect(fn() => $theme->loadThemeFile())->toThrow(\ErrorException::class)
        ->and(fn() => $theme->loadThemeFile())->toThrow(\ErrorException::class);
});

it('returns active theme code from event', function() {
    Event::listen(ThemeGetActiveEvent::class, function() {
        return 'activeThemeCode';
    });
    expect(Theme::getActiveCode())->toBe('activeThemeCode');
});

it('returns the correct active theme code', function() {
    expect(Theme::getActiveCode())->toBe('tests-theme');
});

it('returns default theme code from config when no active theme', function() {
    Event::fake();
    Igniter::shouldReceive('hasDatabase')->andReturn(true);
    \Igniter\Main\Models\Theme::clearDefaultModel();
    \Igniter\Main\Models\Theme::create([
        'name' => 'Default Theme',
        'code' => 'defaultThemeCode',
        'status' => 1,
        'is_default' => 1,
    ]);
    expect(Theme::getActiveCode())->toBe('defaultThemeCode');
    \Igniter\Main\Models\Theme::clearDefaultModel();
});

it('returns the correct form config', function() {
    $theme = new Theme('/path/to/theme', ['code' => 'themeCode']);
    $config = [
        'form' => [
            'general' => [
                'fields' => [
                    'field1' => [
                        'label' => 'Field 1',
                        'type' => 'text',
                    ],
                ],
            ],
        ],
    ];
    File::shouldReceive('getRequire')->andReturn($config);
    File::shouldReceive('isDirectory')->andReturn(true);
    File::shouldReceive('exists')->andReturn(true);

    $formConfig = $theme->getFormConfig();

    expect($formConfig)->toBe($config['form'])
        ->and($theme->hasFormConfig())->toBeTrue();
});

it('returns correct config array', function() {
    $theme = new Theme('/path/to/theme', ['code' => 'themeCode']);
    File::shouldReceive('exists')->andReturn(true);
    File::shouldReceive('getRequire')->andReturn(['form' => ['fields' => ['field1' => 'value1']]]);
    File::shouldReceive('isDirectory')->andReturn(true);

    $config = $theme->getConfig();
    expect($config)->toBe(['form' => ['fields' => ['field1' => 'value1']]])
        ->and($config)->toBe($theme->getConfig());
});

it('returns the correct custom data', function() {
    $themeData = ['field1' => 'value1'];
    \Igniter\Main\Models\Theme::create([
        'code' => 'themeCode',
        'data' => $themeData,
    ]);
    $theme = new Theme('/path/to/theme', ['code' => 'themeCode']);
    $config = [
        'form' => [
            'general' => [
                'fields' => [
                    'field1' => [
                        'label' => 'Field 1',
                        'type' => 'text',
                    ],
                ],
            ],
        ],
    ];
    File::shouldReceive('getRequire')->andReturn($config);
    File::shouldReceive('isDirectory')->andReturn(true);
    File::shouldReceive('exists')->andReturn(true);

    expect($theme->getCustomData())->toBe($themeData)
        ->and($theme->hasCustomData())->toBeTrue()
        ->and($theme->field1)->toBe('value1')
        ->and(isset($theme->field1))->toBeTrue()
        ->and(isset($theme->invalid))->toBeFalse();
});

it('returns empty array when theme data is not available', function() {
    $theme = new Theme('/path/to/theme', ['code' => 'themeCode']);
    app()->instance(ThemeManager::class, $themeManager = mock(ThemeManager::class));
    $themeManager->shouldReceive('findTheme')->andReturn($theme);

    expect($theme->getAssetVariables())->toBe([]);
});

it('returns empty array when no asset variables are defined', function() {
    \Igniter\Main\Models\Theme::clearThemeInstances();
    \Igniter\Main\Models\Theme::create([
        'code' => 'themeCode',
        'data' => ['field1' => 'value1'],
    ]);
    File::shouldReceive('getRequire')->andReturn([
        'form' => [
            'general' => [
                'title' => 'General',
                'fields' => [
                    'field1' => [
                        'label' => 'Field 1',
                        'type' => 'text',
                    ],
                ],
            ],
        ],
    ]);
    File::shouldReceive('isDirectory')->andReturn(true);
    File::shouldReceive('exists')->andReturn(true);
    File::shouldReceive('localToPublic')->andReturn('/public');
    $theme = new Theme('/path/to/theme', ['code' => 'themeCode']);
    app()->instance(ThemeManager::class, $themeManager = mock(ThemeManager::class));
    $themeManager->shouldReceive('findTheme')->andReturn($theme);

    expect($theme->getAssetVariables())->toBe([]);
});

it('returns correct asset variables when defined', function() {
    \Igniter\Main\Models\Theme::clearThemeInstances();
    \Igniter\Main\Models\Theme::create([
        'code' => 'themeCode',
        'data' => ['field1' => 'value1'],
    ]);
    File::shouldReceive('localToPublic')->andReturn('/public');
    File::shouldReceive('getRequire')->andReturn([
        'form' => [
            'general' => [
                'title' => 'General',
                'fields' => [
                    'field1' => [
                        'label' => 'Field 1',
                        'type' => 'text',
                        'assetVar' => 'var1',
                    ],
                ],
            ],
        ],
    ]);
    File::shouldReceive('isDirectory')->andReturn(true);
    File::shouldReceive('exists')->andReturn(true);
    $theme = new Theme('/path/to/theme', ['code' => 'themeCode']);
    app()->instance(ThemeManager::class, $themeManager = mock(ThemeManager::class));
    $themeManager->shouldReceive('findTheme')->andReturn($theme);

    expect($theme->getAssetVariables())->toBe(['var1' => 'value1']);
});

it('returns a collection of pages in the theme', function() {
    expect((new Theme($this->themePath, ['code' => 'themeCode']))->listPages())->toBeCollection();
});

it('returns a collection of partials in the theme', function() {
    expect((new Theme($this->themePath, ['code' => 'themeCode']))->listPartials())->toBeCollection();
});

it('returns a collection of layouts in the theme', function() {
    expect((new Theme($this->themePath, ['code' => 'themeCode']))->listLayouts())->toBeCollection();
});

it('creates new file source when no parent theme exists', function() {
    $theme = new Theme($this->themePath);
    $fileSource = $theme->makeFileSource();

    expect($fileSource)->toBe($theme->makeFileSource());
});

it('creates chain file source when parent theme exists', function() {
    $theme = new Theme($this->themePath, ['parent' => 'parentTheme']);
    app()->instance(ThemeManager::class, $themeManager = mock(ThemeManager::class));
    $themeManager->shouldReceive('findTheme')->andReturn(new Theme($this->themePath));

    $fileSource = $theme->makeFileSource();
    expect($fileSource)->toBeInstanceOf(ChainFileSource::class);
});

it('creates a new template instance for a valid directory name', function() {
    $theme = new Theme($this->themePath);
    $template = $theme->newTemplate('_pages');
    expect($template)->toBeInstanceOf(PageTemplate::class);
});

it('returns the correct template class for a valid directory name', function() {
    $theme = new Theme($this->themePath);
    expect($theme->getTemplateClass('_pages'))->toBe(PageTemplate::class);
});

it('throws exception when getting template class for an invalid directory name', function() {
    $theme = new Theme($this->themePath);
    expect(fn() => $theme->getTemplateClass('_invalid'))->toThrow(RuntimeException::class, 'Source Model not found for [_invalid].');
});
