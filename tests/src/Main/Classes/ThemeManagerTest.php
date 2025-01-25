<?php

namespace Igniter\Tests\Main\Classes;

use Igniter\Flame\Composer\Manager as ComposerManager;
use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Pagic\Contracts\TemplateInterface;
use Igniter\Flame\Support\Facades\File;
use Igniter\Flame\Support\Facades\Igniter;
use Igniter\Main\Classes\Theme;
use Igniter\Main\Classes\ThemeManager;
use Igniter\System\Classes\PackageManifest;
use Igniter\System\Classes\UpdateManager;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

beforeEach(function() {
    $this->themePath = testThemePath();
    $this->themeManager = resolve(ThemeManager::class);
});

it('lists all themes in the system', function() {
    expect($this->themeManager->listThemes())
        ->toBeArray()
        ->toHaveCount(2)
        ->and($this->themeManager->bootThemes())->toBeNull();
});

it('throws exception when theme meta file is missing', function() {
    expect(fn() => $this->themeManager->loadTheme('/path/to/missing-theme'))
        ->toThrow(SystemException::class, 'Theme does not have a registration file in: /path/to/missing-theme');
});

it('returns null when loading theme with invalid theme code', function() {
    $themePath = '/path/to/theme-with-invalid-code';
    File::shouldReceive('exists')->with($themePath.'/theme.json')->andReturnTrue();
    File::shouldReceive('json')->with($themePath.'/theme.json')->andReturn([
        'code' => 'invalid code',
    ]);

    expect($this->themeManager->loadTheme($themePath))->toBeNull();
});

it('returns null when loading theme with invalid theme path', function() {
    $themePath = '/path/to/theme-with invalid path';
    File::shouldReceive('exists')->with($themePath.'/theme.json')->andReturnTrue();
    File::shouldReceive('json')->with($themePath.'/theme.json')->andReturn([]);

    expect($this->themeManager->loadTheme($themePath))->toBeNull();
});

it('boots theme correctly', function() {
    $oldPublishGroups = ServiceProvider::$publishGroups['igniter-assets'];
    unset(ServiceProvider::$publishGroups['igniter-assets']);
    $this->themeManager->themes['parentTheme'] = $parentTheme = new Theme($this->themePath, ['code' => 'parentTheme']);
    $parentTheme->active = true;

    $theme = new Theme($this->themePath, [
        'code' => 'tests-theme',
        'name' => 'Tests Theme',
        'publish-paths' => ['/public'],
        'source-path' => '/',
        'parent' => 'parentTheme',
    ]);
    $theme->active = true;

    $this->themeManager->bootTheme($theme);

    expect(ServiceProvider::$publishGroups['igniter-assets'])->toContain(public_path('vendor/tests-theme'))
        ->and(View::exists('tests-theme::_layouts/default'))->toBeTrue()
        ->and(View::exists('parentTheme::_layouts/default'))->toBeTrue();

    ServiceProvider::$publishGroups['igniter-assets'] = $oldPublishGroups;
});

it('checks active theme', function() {
    expect($this->themeManager->getActiveTheme())
        ->getName()
        ->toEqual('tests-theme')
        ->and($this->themeManager->isActive('invalid code'))->toBeFalse();
});

it('finds test theme', function() {
    expect($this->themeManager->findTheme('tests-theme')->getPath())->toStartWith($this->themePath);
});

it('finds parent theme using child theme code', function() {
    $this->themeManager->themes['parentTheme'] = new Theme(dirname($this->themePath), ['code' => 'parentTheme']);
    $this->themeManager->themes['childTheme'] = new Theme($this->themePath, [
        'code' => 'tests-theme',
        'parent' => 'parentTheme',
    ]);

    expect($this->themeManager->findParent('childTheme')->getPath())->toStartWith(dirname($this->themePath));
});

it('lists theme within themes directory', function() {
    $themesPath = '/path/to/themes';
    $customPath = '/path/to/custom-themes';
    $this->themeManager->clearDirectory();
    $this->themeManager->addDirectory($customPath);
    Igniter::shouldReceive('themesPath')->andReturn($themesPath);
    File::shouldReceive('isDirectory')->with($themesPath)->andReturnTrue();
    File::shouldReceive('glob')->with($themesPath.'/*/theme.json')->andReturn([
        $themesPath.'/theme1/theme.json',
    ]);
    File::shouldReceive('glob')->with($customPath.'/*/theme.json')->andReturn([
        $customPath.'/theme2/theme.json',
    ]);

    expect($this->themeManager->folders($customPath))
        ->toContain($themesPath.'/theme1', $customPath.'/theme2');
});

it('does not allow errors as a theme code', function() {
    expect($this->themeManager->checkName('errors'))->toBeNull();
});

it('checks a theme is locked correctly', function() {
    $theme = new Theme($this->themePath, ['code' => 'tests-theme']);
    $theme->locked = true;
    $this->themeManager->themes['lockedTheme'] = $theme;

    expect($this->themeManager->isLocked('lockedTheme'))->toBeTrue();
});

it('checks a theme path is locked', function() {
    $this->themeManager->themes['parentTheme'] = $parentTheme = new Theme($this->themePath, [
        'code' => 'parentTheme',
    ]);
    $theme = new Theme(__DIR__, ['code' => 'tests-theme', 'parent' => 'parentTheme']);
    $parentTheme->locked = true;
    $this->themeManager->themes['lockedTheme'] = $theme;

    expect($this->themeManager->isLockedPath($this->themePath.'/path/to/check', $theme))->toBeTrue();

    $theme = new Theme(__DIR__, ['code' => 'tests-theme']);

    expect($this->themeManager->isLockedPath($this->themePath.'/path/to/check', $theme))->toBeTrue();

    $theme = new Theme(__DIR__, ['code' => 'tests-theme']);
    $theme->locked = true;

    expect($this->themeManager->isLockedPath(__DIR__.'/path/to/check', $theme))->toBeTrue();
});

it('checks a parent theme has child theme', function() {
    $this->themeManager->themes['parentTheme'] = new Theme($this->themePath, ['code' => 'parentTheme']);
    $this->themeManager->themes['childTheme'] = new Theme($this->themePath, [
        'code' => 'tests-theme',
        'parent' => 'parentTheme',
    ]);

    expect($this->themeManager->checkParent('parentTheme'))->toBeTrue()
        ->and($this->themeManager->checkParent('childTheme'))->toBeFalse();
});

it('finds a theme file', function() {
    $file = '_pages/components.blade.php';

    expect($this->themeManager->findFile($file, 'tests-theme'))->toStartWith($this->themePath)
        ->and($this->themeManager->findFile($file, 'tests-theme', 'base'))->toBeFalse();
});

it('fails when theme file does not exist', function() {
    expect($this->themeManager->findFile('_pages/compone.blade.php', 'tests-theme'))->toBeFalse();
});

it('reads an existing theme file successfully', function() {
    expect($this->themeManager->readFile('_pages/components', 'tests-theme'))
        ->toBeInstanceOf(TemplateInterface::class);
});

it('throws exception when reading a non-existent theme file', function() {
    expect(fn() => $this->themeManager->readFile('_pages/compo', 'tests-theme'))
        ->toThrow(SystemException::class, 'Theme template file not found: _pages/compo');
});

it('creates a new theme file successfully', function() {
    File::shouldReceive('extension')->with($this->themePath.'/_pages/fileName')->andReturn(false);
    File::shouldReceive('isFile')->with($this->themePath.'/_pages/fileName.blade.php')->andReturn(false);
    File::shouldReceive('isDirectory')->with($this->themePath.'/_pages')->andReturn(false);
    File::shouldReceive('dirname')->with($this->themePath.'/_pages/fileName.blade.php')->andReturn($this->themePath.'/_pages');
    File::shouldReceive('makeDirectory')->with($this->themePath.'/_pages', 0777, true, true);
    File::shouldReceive('put')->with($this->themePath.'/_pages/fileName.blade.php', "\n")->andReturn(true);

    expect($this->themeManager->newFile('_pages/fileName', 'tests-theme'))
        ->toBe($this->themePath.'/_pages/fileName.blade.php');
});

it('throws exception when creating a file that already exists', function() {
    File::shouldReceive('extension')->with($this->themePath.'/_pages/fileName')->andReturn(false);
    File::shouldReceive('isFile')->with($this->themePath.'/_pages/fileName.blade.php')->andReturn(true);
    File::shouldReceive('put')->with($this->themePath.'/_pages/fileName.blade.php', "\n")->andReturn(true);

    expect(fn() => $this->themeManager->newFile('_pages/fileName', 'tests-theme'))
        ->toThrow(SystemException::class, 'Theme template file already exists: _pages/fileName');
});

it('writes to an existing theme file successfully', function() {
    expect($this->themeManager->writeFile('_content/test-content', [
        'markup' => 'This is a test content',
    ], 'tests-theme'))->toBeTrue();
});

it('throws exception when writing to a non-existent theme file', function() {
    expect(fn() => $this->themeManager->writeFile('_pages/fileName', [
        'key' => 'value',
    ], 'tests-theme'))->toThrow(SystemException::class, 'Theme template file not found: _pages/fileName');
});

it('renames an existing theme file successfully', function() {
    $oldFile = '_pages/components';
    $newFile = '_pages/compon';

    expect($this->themeManager->renameFile($oldFile, $newFile, 'tests-theme'))->toBeTrue()
        ->and($this->themeManager->renameFile($newFile, $oldFile, 'tests-theme'))->toBeTrue();
});

it('throws exception when renaming a non-existent theme file', function() {
    expect(fn() => $this->themeManager->renameFile('_pages/fileName', '_pages/newFileName', 'tests-theme'))
        ->toThrow(SystemException::class, 'Theme template file not found: _pages/fileName');
});

it('throws exception when renaming a locked theme file', function() {
    $theme = $this->themeManager->findTheme('tests-theme');
    $theme->locked = true;

    expect(fn() => $this->themeManager->renameFile('_pages/components', '_pages/newFileName', 'tests-theme'))
        ->toThrow(SystemException::class, lang('igniter::system.themes.alert_theme_path_locked'));
});

it('throws exception when renaming to an existing theme file', function() {
    $oldFile = '_pages/components';
    $newFile = '_pages/components';

    expect(fn() => $this->themeManager->renameFile($oldFile, $newFile, 'tests-theme'))
        ->toThrow(SystemException::class, 'Theme template file already exists: _pages/components');
});

it('deletes an existing theme file successfully', function() {
    File::put($this->themePath.'/_pages/fileName.blade.php', "\n");

    expect($this->themeManager->deleteFile('_pages/fileName', 'tests-theme'))->toBeTrue();
});

it('throws exception when deleting a non-existent theme file', function() {
    expect(fn() => $this->themeManager->deleteFile('_pages/fileName', 'tests-theme'))
        ->toThrow(SystemException::class, 'Theme template file not found: _pages/fileName');
});

it('throws exception when deleting a locked theme file', function() {
    $theme = $this->themeManager->findTheme('tests-theme');
    $theme->locked = true;

    expect(fn() => $this->themeManager->deleteFile('_pages/components', 'tests-theme'))
        ->toThrow(SystemException::class, lang('igniter::system.themes.alert_theme_path_locked'));
});

it('removes theme folder successfully', function() {
    File::shouldReceive('isDirectory')->with($this->themePath)->andReturn(true);
    File::shouldReceive('deleteDirectory')->with($this->themePath)->andReturn(true);

    expect($this->themeManager->removeTheme('tests-theme'))->toBeTrue();
});

it('returns false when removing non-existent theme folder', function() {
    expect($this->themeManager->removeTheme('themeCode'))->toBeFalse();
});

it('deletes theme and its data successfully', function() {
    app()->instance(ComposerManager::class, $composerManager = mock(ComposerManager::class));
    $composerManager->shouldReceive('getPackageName')->with('tests-theme')->andReturn('packageName');
    $composerManager->shouldReceive('uninstall')->andReturnSelf();

    File::shouldReceive('isDirectory')->with($this->themePath)->andReturn(true);
    File::shouldReceive('deleteDirectory')->with($this->themePath)->andReturn(true);

    app()->instance(UpdateManager::class, $updateManager = mock(UpdateManager::class));
    $updateManager->shouldReceive('purgeExtension')->with('tests-theme')->andReturnSelf();

    expect($this->themeManager->deleteTheme('tests-theme', true))->toBeNull();
});

it('installs theme successfully', function() {
    \Igniter\Main\Models\Theme::create([
        'code' => 'tests-theme',
        'name' => 'Theme Name',
    ]);

    app()->instance(PackageManifest::class, $packageManifest = mock(PackageManifest::class));
    $packageManifest->shouldReceive('getVersion')->with('tests-theme')->andReturn('1.0.0');

    expect($this->themeManager->installTheme('tests-theme'))->toBeTrue();
});

it('returns false when install theme can not be found', function() {
    \Igniter\Main\Models\Theme::create([
        'code' => 'invalid-theme',
        'name' => 'Theme Name',
    ]);

    expect($this->themeManager->installTheme('invalid-theme'))->toBeFalse();
});

it('updates installed themes config value', function() {
    app()->instance(PackageManifest::class, $packageManifest = mock(PackageManifest::class));
    $packageManifest->shouldReceive('writeDisabled');
    $this->themeManager->disabledThemes = ['themeCode' => true];

    $this->themeManager->updateInstalledThemes('tests-theme');

    expect($this->themeManager->disabledThemes)->not->toHaveKey('tests-theme');

    $this->themeManager->updateInstalledThemes('tests-theme', false);

    expect($this->themeManager->disabledThemes)->toHaveKey('tests-theme');
});

it('creates child theme successfully', function() {
    $this->themeManager->clearDirectory();
    File::shouldReceive('isDirectory')->with(base_path('/themes/child-theme'))->andReturn(false);
    File::shouldReceive('makeDirectory')->with(base_path('/themes/child-theme'), 0777, true, true);
    File::shouldReceive('put')->withSomeOfArgs(base_path('/themes/child-theme/theme.json'))->andReturnTrue();
    File::shouldReceive('isDirectory')->with($this->themePath.'/_meta')->andReturn(true);
    File::shouldReceive('isDirectory')->with($this->themePath.'/meta')->andReturn(false);
    app()->instance(PackageManifest::class, $packageManifest = mock(PackageManifest::class));
    $packageManifest->shouldReceive('themes')->andReturn([]);
    File::shouldReceive('isDirectory')->with(base_path('/themes'))->andReturn(true);
    File::shouldReceive('glob')->with(base_path('/themes/*/theme.json'))->andReturn([
        base_path('/themes/child-theme/theme.json'),
    ]);
    File::shouldReceive('exists')->with(base_path('/themes/child-theme/theme.json'))->andReturn(true);
    File::shouldReceive('exists')->with(base_path('/themes/child-theme/theme.json'))->andReturn(true);
    File::shouldReceive('json')->with(base_path('/themes/child-theme/theme.json'))->andReturn([
        'code' => 'childThemeCode',
        'name' => 'Child Theme',
        'description' => 'Description',
        'author' => 'Author',
    ]);
    File::shouldReceive('localToPublic')->andReturn('public');
    File::shouldReceive('isDirectory')->andReturn(false);
    File::shouldReceive('exists')->andReturn(false);

    $childTheme = $this->themeManager->createChildTheme('tests-theme', 'child-theme');

    expect($childTheme->code)->toBe('child-theme')
        ->and($childTheme->name)->toBe('Tests Theme [child]')
        ->and($childTheme->description)->toBe('A Test theme for TastyIgniter front-end')
        ->and($childTheme->data)->toBe([]);
});
