<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\Template;

use Igniter\Flame\Support\Facades\File as FileFacade;
use Igniter\Main\Classes\Theme;
use Igniter\Main\Template\File;
use Igniter\Main\Template\Page;

it('initializes correctly', function() {
    expect(File::DIR_NAME)->toBe('')
        ->and(File::TYPE_KEY)->toBe('_files')
        ->and((new File)->getMaxNesting())->toBeNull();
});

it('detects reserved template paths', function(string $path, bool $reserved) {
    expect(File::isReservedPath($path))->toBe($reserved);
})->with([
    'pages path' => ['_pages/home', true],
    'partials path' => ['_partials/header', true],
    'layouts path' => ['_layouts/default', true],
    'content path' => ['_content/about', true],
    'meta path' => ['_meta/fields', true],
    'assets path' => ['assets/js/app', true],
    'root file' => ['test-file', false],
    'nested file' => ['components/account/dashboard', false],
]);

it('lists only non-reserved blade files including nested paths', function() {
    $files = File::listInTheme('tests-theme', true)->map->getFileName()->all();

    expect($files)->toContain('test-file.blade.php')
        ->and($files)->toContain('components/account/dashboard.blade.php')
        ->and($files)->not->toContain('components.blade.php')
        ->and(collect($files)->contains(fn(string $file): bool => str_starts_with($file, '_pages/')))->toBeFalse()
        ->and(collect($files)->contains(fn(string $file): bool => str_starts_with($file, '_partials/')))->toBeFalse();
});

it('loads a nested file from the theme source root', function() {
    $file = File::load('tests-theme', 'components/account/dashboard');

    expect($file)->not->toBeNull()
        ->and($file->getBaseFileName())->toBe('components/account/dashboard')
        ->and($file->getMarkup())->toContain('deeply nested')
        ->and($file->getFilePath())->toStartWith(testThemePath());
});

it('resolves file paths from Theme::getSourcePath when source-path is configured', function() {
    $themePath = storage_path('framework/testing/source-path-theme-'.uniqid());
    $sourcePath = $themePath.'/templates';
    FileFacade::makeDirectory($sourcePath.'/mail', 0777, true, true);
    FileFacade::put($sourcePath.'/mail/order.blade.php', "source path file\n");
    FileFacade::put($themePath.'/root-only.blade.php', "theme root file\n");

    $theme = new Theme($themePath, [
        'code' => 'source-path-files-theme',
        'name' => 'Source Path Files Theme',
        'source-path' => '/templates',
    ]);

    expect($theme->getPath())->toBe($themePath)
        ->and($theme->getSourcePath())->toBe($sourcePath);

    Page::getSourceResolver()->addSource($theme->getName(), $theme->makeFileSource());

    $files = File::listInTheme($theme->getName(), true)->map->getFileName()->all();
    $file = File::load($theme->getName(), 'mail/order');

    expect($files)->toContain('mail/order.blade.php')
        ->and($files)->not->toContain('root-only.blade.php')
        ->and($file)->not->toBeNull()
        ->and($file->getFilePath())->toBe($sourcePath.'/mail/order.blade.php');

    FileFacade::deleteDirectory($themePath);
});

it('allows saving deeply nested file names', function() {
    $file = File::load('tests-theme', 'components/account/dashboard');
    $originalMarkup = $file->getMarkup();

    expect($file->fill(['markup' => 'updated nested content'])->save())->toBeTrue();

    $reloaded = File::load('tests-theme', 'components/account/dashboard');
    expect($reloaded->getMarkup())->toContain('updated nested content');

    $reloaded->fill(['markup' => $originalMarkup])->save();
});
