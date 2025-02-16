<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Classes;

use Facades\Igniter\System\Helpers\SystemHelper;
use Igniter\Flame\Composer\Manager as ComposerManager;
use Igniter\Flame\Database\Migrations\Migrator;
use Igniter\Flame\Exception\ApplicationException;
use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Support\Facades\Igniter;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Models\Theme;
use Igniter\System\Classes\ExtensionManager;
use Igniter\System\Classes\UpdateManager;
use Igniter\System\Database\Seeds\DatabaseSeeder;
use Igniter\System\Models\Extension;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Console\Output\OutputInterface;

it('logs messages correctly', function() {
    $updateManager = resolve(UpdateManager::class);
    $updateManager->log('Test message');
    expect($updateManager->getLogs())->toBe(['Test message']);
});

it('resets logs correctly', function() {
    $updateManager = resolve(UpdateManager::class);
    $updateManager->log('Test message');
    $updateManager->resetLogs();
    expect($updateManager->getLogs())->toBe([]);
});

it('logs error if migration table not found during down', function() {
    $migrator = mock(Migrator::class);
    app()->instance('migrator', $migrator);
    $updateManager = new UpdateManager;
    $migrator->shouldReceive('repositoryExists')->andReturn(false);

    $updateManager->down();
    expect($updateManager->getLogs())->toContain('<error>Migration table not found.</error>');
});

it('rolls back extensions and core migrations during down', function() {
    $migrator = mock(Migrator::class);
    app()->instance('migrator', $migrator);
    $migrator->shouldReceive('repositoryExists')->andReturn(true);
    $migrator->shouldReceive('resetAll')->times(2);
    Igniter::shouldReceive('migrationPath')->andReturn([
        'test.extension' => 'path/to/migrations',
    ]);
    $migrator->shouldReceive('setOutput');
    Igniter::shouldReceive('coreMigrationPath')->andReturn([
        'igniter.system' => ['path/to/migrations'],
    ]);
    $updateManager = new UpdateManager;
    $outputMock = mock(OutputInterface::class);
    $outputMock->shouldReceive('writeln');
    $updateManager->setLogsOutput($outputMock);

    $updateManager->down();
    $logs = $updateManager->getLogs();

    expect($logs[0])->toContain('<info>Purging extension test.extension</info>')
        ->and($logs[1])->toContain('<info>Rolling back igniter.system</info>')
        ->and($logs[2])->toContain('<info>Rolled back igniter.system</info>');
});

it('runs core and extension migrations during migrate', function() {
    $updateManager = mockMigrate();

    $updateManager->migrate();

    expect($updateManager->getLogs())->toBeEmpty();
});

it('logs error if unable to find migrations for extension during migrate', function() {
    $updateManager = new UpdateManager;
    Igniter::shouldReceive('migrationPath')->andReturn([]);

    $updateManager->migrateExtension('nonexistent-extension');

    expect($updateManager->getLogs())->toContain('<error>Unable to find migrations for:</error> nonexistent-extension');
});

it('migrates extension correctly', function() {
    $migrator = mock(Migrator::class);
    app()->instance('migrator', $migrator);
    $migrator->shouldReceive('setOutput');
    $updateManager = new UpdateManager;
    Igniter::shouldReceive('migrationPath')->andReturn(['test.extension' => ['/path/to/migrations']]);
    $outputMock = mock(OutputInterface::class);
    $outputMock->shouldReceive('writeln');
    $updateManager->setLogsOutput($outputMock);
    $migrator->shouldReceive('runGroup')->once();

    $updateManager->migrateExtension('test.extension');

    expect($updateManager->getLogs())->toContain('<info>Migrating extension test.extension</info>');
});

it('logs error if migration table not found during purge extension', function() {
    $updateManager = new UpdateManager;
    Igniter::shouldReceive('migrationPath')->andReturn([]);

    $updateManager->purgeExtension('nonexistent.extension');

    expect($updateManager->getLogs())->toContain('<error>Unable to find migrations for:</error> nonexistent.extension');
});

it('logs error if unable to find migrations for extension during rollback', function() {
    $updateManager = new UpdateManager;
    Igniter::shouldReceive('migrationPath')->andReturn([]);

    $updateManager->rollbackExtension('nonexistent-extension');

    expect($updateManager->getLogs())->toContain('<error>Unable to find migrations for:</error> nonexistent-extension');
});

it('rolls back extension migrations correctly', function() {
    $migrator = mock(Migrator::class);
    app()->instance('migrator', $migrator);
    $migrator->shouldReceive('setOutput');
    $updateManager = new UpdateManager;
    $migrator->shouldReceive('rollbackAll')->once();
    Igniter::shouldReceive('migrationPath')->andReturn(['test.extension' => ['/path/to/migrations']]);
    $outputMock = mock(OutputInterface::class);
    $outputMock->shouldReceive('writeln');
    $updateManager->setLogsOutput($outputMock);

    $updateManager->rollbackExtension('test.extension');

    expect($updateManager->getLogs())->toContain('<info>Rolling back extension test.extension</info>');
});

it('returns true if last check is due', function() {
    mockRequestUpdate();
    $updateManager = resolve(UpdateManager::class);
    $result = $updateManager->isLastCheckDue();
    expect($result)->toBeTrue();
});

it('returns recommended items with installed status', function() {
    mockInstalledItems();
    $updateManager = new UpdateManager;

    $result = $updateManager->listItems('extension');

    expect($result['data'][0]['code'])->toBe('extension1')
        ->and($result['data'][0]['installed'])->toBeTrue()
        ->and($result['data'][1]['code'])->toBe('extension2')
        ->and($result['data'][1]['installed'])->toBeFalse();
});

it('returns searched items with installed status', function() {
    mockInstalledItems();
    $updateManager = new UpdateManager;

    $result = $updateManager->searchItems('extension', 'searchQuery');

    expect($result['data'][0]['code'])->toBe('extension1')
        ->and($result['data'][0]['installed'])->toBeTrue()
        ->and($result['data'][1]['code'])->toBe('extension2')
        ->and($result['data'][1]['installed'])->toBeFalse();
});

it('applies site detail correctly', function() {
    $expectedResponse = [
        'data' => [
            'name' => 'Test Site',
            'url' => 'https://test-site.com',
        ],
    ];
    Http::fake(['https://api.tastyigniter.com/v2/site/detail' => Http::response($expectedResponse)]);
    $updateManager = resolve(UpdateManager::class);
    app()->setBasePath(__DIR__.'/../Fixtures');

    $result = $updateManager->applySiteDetail('test-key');

    expect($result)->toBeArray()
        ->and(setting()->getPref('carte_key'))->toBe('test-key')
        ->and($updateManager->getSiteDetail())->toBe($result);
});

it('returns extensions installed items correctly', function() {
    Extension::create(['name' => 'extension1', 'version' => '1.0.0']);
    $expectedResponse = [
        'data' => [
            ['code' => 'extension1', 'icon' => null],
            ['code' => 'extension2', 'icon' => null],
        ],
    ];
    Http::fake(['https://api.tastyigniter.com/v2/items' => Http::response($expectedResponse)]);
    $updateManager = resolve(UpdateManager::class);

    $result = $updateManager->getInstalledItems('extensions');

    expect($result)->toBeArray()
        ->and($result)->toBe($updateManager->getInstalledItems('extensions'));
});

it('applies items correctly', function() {
    config(['igniter-system.disableCoreUpdates' => true]);
    $expectedResponse = [
        'data' => [
            [
                'code' => 'core-package',
                'type' => 'core',
                'package' => 'item1/package',
                'name' => 'Package1',
                'version' => '1.0.0',
                'author' => 'Sam',
            ],
            [
                'code' => 'package1',
                'type' => 'extension',
                'package' => 'item2/package',
                'name' => 'Package2',
                'version' => '1.0.0',
                'author' => 'Sam',
            ],
        ],
    ];
    Http::fake(['https://api.tastyigniter.com/v2/core/apply' => Http::response($expectedResponse)]);
    $updateManager = new UpdateManager;

    $result = $updateManager->requestApplyItems(['package1', 'core-package']);

    expect($result->count())->toBe(1)
        ->and($result->first()->code)->toBe('package1');
});

it('marks update as ignored', function() {
    $updateManager = new UpdateManager;

    $updateManager->markedAsIgnored('package1');

    expect($updateManager->getIgnoredUpdates())->toBe(['package1' => true]);
});

it('returns null if pre-install checks fail on assertIniSet', function() {
    $updateManager = resolve(UpdateManager::class);
    SystemHelper::shouldReceive('assertIniSet')->andReturn(false);
    expect($updateManager->preInstall())->toBeNull();
});

it('throws exception if pre-install checks fail on assertIniMaxExecutionTime', function() {
    $updateManager = resolve(UpdateManager::class);
    SystemHelper::shouldReceive('assertIniSet')->andReturn(true);
    SystemHelper::shouldReceive('assertIniMaxExecutionTime')->andReturn(true);
    SystemHelper::shouldReceive('assertIniMemoryLimit')->andReturn(false);
    expect(fn() => $updateManager->preInstall())->toThrow(ApplicationException::class);
});

it('throws exception if pre-install checks fail on assertIniMemoryLimit', function() {
    $updateManager = resolve(UpdateManager::class);
    SystemHelper::shouldReceive('assertIniSet')->andReturn(true);
    SystemHelper::shouldReceive('assertIniMaxExecutionTime')->andReturn(false);
    SystemHelper::shouldReceive('assertIniMemoryLimit')->andReturn(true);
    expect(fn() => $updateManager->preInstall())->toThrow(ApplicationException::class);
});

it('installs packages correctly', function() {
    $composerManager = mock(ComposerManager::class);
    app()->instance(ComposerManager::class, $composerManager);
    $composerManager->shouldReceive('install')->once();

    $updateManager = resolve(UpdateManager::class);

    $updateManager->install([
        [
            'code' => 'test.extension',
            'package' => 'test/extension',
            'type' => 'extension',
            'name' => 'Test Package',
            'version' => '2.0.0',
            'author' => 'Sam',
            'description' => 'Test package description',
            'icon' => 'fa-icon',
            'installedVersion' => '1.0.0',
            'publishedAt' => '2021-01-01 00:00:00',
            'tags' => [],
            'hash' => 'hash',
            'updatedAt' => '2021-01-01 00:00:00',
        ],
    ]);

    expect($updateManager->getLogs()[0])->toContain('Test Package (1.0.0 => 2.0.0)');
});

it('completes installation correctly', function() {
    mockRequestUpdate();
    $extensionManager = mock(ExtensionManager::class);
    $themeManager = mock(ThemeManager::class);
    app()->instance(ExtensionManager::class, $extensionManager);
    app()->instance(ThemeManager::class, $themeManager);
    $extensionManager->shouldReceive('installExtension')->with('test.extension', '2.0.0')->once();
    $themeManager->shouldReceive('installTheme')->with('test.theme', '2.0.0')->once();
    $requirements = [
        [
            'code' => 'test.core',
            'package' => 'test/core',
            'type' => 'core',
            'name' => 'Test Package',
            'version' => '2.0.0',
            'author' => 'Sam',
            'description' => 'Test package description',
            'icon' => 'fa-icon',
            'installedVersion' => '1.0.0',
            'publishedAt' => '2021-01-01 00:00:00',
            'tags' => [],
            'hash' => 'hash',
            'updatedAt' => '2021-01-01 00:00:00',
        ],
        [
            'code' => 'test.extension',
            'package' => 'test/extension',
            'type' => 'extension',
            'name' => 'Test Package',
            'version' => '2.0.0',
            'author' => 'Sam',
            'description' => 'Test package description',
            'icon' => 'fa-icon',
            'installedVersion' => '1.0.0',
            'publishedAt' => '2021-01-01 00:00:00',
            'tags' => [],
            'hash' => 'hash',
            'updatedAt' => '2021-01-01 00:00:00',
        ],
        [
            'code' => 'test.theme',
            'package' => 'test/theme',
            'type' => 'theme',
            'name' => 'Test Package',
            'version' => '2.0.0',
            'author' => 'Sam',
            'description' => 'Test package description',
            'icon' => 'fa-icon',
            'installedVersion' => '1.0.0',
            'publishedAt' => '2021-01-01 00:00:00',
            'tags' => [],
            'hash' => 'hash',
            'updatedAt' => '2021-01-01 00:00:00',
        ],
    ];

    $updateManager = mockMigrate();
    expect(fn() => $updateManager->completeInstall($requirements))->toThrow(SystemException::class);
});

function mockRequestUpdate()
{
    $expectedResponse = [
        'data' => [
            [
                'code' => 'item1',
                'type' => 'core',
                'package' => 'item1/package',
                'name' => 'Package1',
                'version' => '1.0.0',
                'author' => 'Sam',
            ],
            [
                'code' => 'item2',
                'type' => 'extension',
                'package' => 'item2/package',
                'name' => 'Package2',
                'version' => '1.0.0',
                'author' => 'Sam',
            ],
        ],
    ];
    Http::fake(['https://api.tastyigniter.com/v2/core/apply' => Http::response($expectedResponse)]);
}

function mockInstalledItems(): void
{
    Extension::create(['name' => 'extension1', 'version' => '1.0.0']);
    Theme::factory()->create(['code' => 'theme1', 'name' => 'Theme', 'version' => '1.0.0', 'data' => []]);
    $expectedResponse = [
        'data' => [
            ['code' => 'extension1', 'icon' => null],
            ['code' => 'extension2', 'icon' => null],
        ],
    ];
    Http::fake(['https://api.tastyigniter.com/v2/items' => Http::response($expectedResponse)]);
}

function mockMigrate(): UpdateManager
{
    $migrator = mock(Migrator::class);
    app()->instance('migrator', $migrator);
    $migrator->shouldReceive('setOutput');
    $migrator->shouldReceive('runGroup')->twice();
    $databaseSeeder = mock(DatabaseSeeder::class)->makePartial();
    $databaseSeeder->shouldReceive('run');
    app()->instance(DatabaseSeeder::class, $databaseSeeder);
    $updateManager = new UpdateManager;
    $outputMock = mock(OutputInterface::class);
    $outputMock->shouldReceive('writeln');
    $updateManager->setLogsOutput($outputMock);

    return $updateManager;
}
