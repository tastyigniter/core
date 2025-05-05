<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Classes;

use Facades\Igniter\System\Helpers\SystemHelper;
use Igniter\Flame\Composer\Manager as ComposerManager;
use Igniter\Flame\Database\Migrations\Migrator;
use Igniter\Flame\Exception\ApplicationException;
use Igniter\Flame\Support\Facades\Igniter;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Models\Theme;
use Igniter\System\Classes\ExtensionManager;
use Igniter\System\Classes\HubManager;
use Igniter\System\Classes\PackageInfo;
use Igniter\System\Classes\UpdateManager;
use Igniter\System\Database\Seeds\DatabaseSeeder;
use Igniter\System\Models\Extension;
use Igniter\System\Models\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Console\Output\OutputInterface;
use UnexpectedValueException;

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

it('returns empty result when no outdated items are found', function() {
    $manager = new UpdateManager();

    $composerManager = mock(ComposerManager::class);
    app()->instance(ComposerManager::class, $composerManager);
    $composerManager->shouldReceive('listInstalledPackages')->andReturn(collect([]));

    $result = $manager->requestUpdateList();

    expect($result['count'])->toBe(0)
        ->and($result['items'])->toBeEmpty()
        ->and($result['ignoredItems'])->toBeEmpty();
});

it('requests for items to update', function() {
    mockRequestUpdateItems();
    Settings::setPref([
        'carte_key' => 'test-key',
        'carte_info' => [
            'name' => 'Test Site',
            'email' => 'test@example.com',
        ],
    ]);
    $manager = new UpdateManager();
    $result = $manager->requestUpdateList();

    expect($result['count'])->toBe(2)
        ->and($result['items'])->toBeCollection()
        ->and($result['items']->get(0)->code)->toBe('tastyigniter')
        ->and($result['items']->get(1)->code)->toBe('igniter.test')
        ->and($result['ignoredItems'])->toBeCollection()
        ->and($result['ignoredItems']->get(0)->code)->toBe('igniter.ignored');
});

it('excludes core updates when core updates are disabled', function() {
    config(['igniter-system.disableCoreUpdates' => true]);
    mockRequestUpdateItems();
    $manager = new UpdateManager();
    $result = $manager->requestUpdateList();

    expect($result['count'])->toBe(1)
        ->and($result['items'])->toBeCollection()
        ->and($result['items']->get(0)->code)->toBe('igniter.test');
});

it('returns true if last check is due', function() {
    mockRequestUpdateItems();
    $updateManager = resolve(UpdateManager::class);

    expect($updateManager->isLastCheckDue())->toBeTrue();
});

it('applies carte info correctly', function() {
    $expectedResponse = [
        'data' => [
            'name' => 'Test Site',
            'url' => 'https://test-site.com',
        ],
    ];
    Http::fake(['https://api.tastyigniter.com/v2/site/detail' => Http::response($expectedResponse)]);
    $updateManager = resolve(UpdateManager::class);
    app()->setBasePath(__DIR__.'/../Fixtures');

    $result = $updateManager->applyCarte('test-key');

    expect($result)->toBeArray()
        ->and(setting()->getPref('carte_key'))->toBe('test-key')
        ->and($updateManager->getCarteInfo())->toBe($result)
        ->and($updateManager->hasValidCarte())->toBeTrue();

    $updateManager->clearCarte();

    expect(setting()->getPref('carte_key'))->toBeNull()
        ->and($updateManager->hasValidCarte())->toBeFalse();
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
    Settings::setPref([
        'carte_key' => 'test-key',
        'carte_info' => [
            'name' => 'Test Site',
            'email' => 'test@example.com',
        ],
    ]);
    $composerManager = mock(ComposerManager::class);
    app()->instance(ComposerManager::class, $composerManager);
    $composerManager->shouldReceive('listInstalledPackages')->andReturn(collect([
        [
            'name' => 'test/extension',
            'type' => 'tastyigniter-package',
            'version' => '2.0.0',
            'extra' => [
                'tastyigniter-extension' => [
                    'code' => 'test.extension',
                    'description' => 'Test extension description',
                    'icon' => 'fa-icon',
                    'author' => 'Sam',
                    'tags' => [],
                ],
            ],
        ],
        [
            'name' => 'test/theme',
            'type' => 'tastyigniter-theme',
            'version' => '2.0.0',
            'extra' => [
                'tastyigniter-theme' => [
                    'code' => 'test.theme',
                    'description' => 'Test theme description',
                    'icon' => 'fa-icon',
                    'author' => 'Sam',
                    'tags' => [],
                ],
            ],
        ],
    ]));
    $composerManager->shouldReceive('install')->once();
    $composerManager->shouldReceive('assertSchema')->once();
    $composerManager->shouldReceive('addAuthCredentials')->once();

    $updateManager = resolve(UpdateManager::class);
    $installed = $updateManager->install([
        PackageInfo::fromArray([
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
        ]),
        PackageInfo::fromArray([
            'code' => 'test.theme',
            'package' => 'test/theme',
            'type' => 'theme',
            'name' => 'Test Package',
            'version' => '1.0.0',
            'author' => 'Sam',
            'description' => 'Test package description',
            'icon' => 'fa-icon',
            'installedVersion' => '1.0.0',
            'publishedAt' => '2021-01-01 00:00:00',
            'tags' => [],
            'hash' => 'hash',
            'updatedAt' => '2021-01-01 00:00:00',
        ]),
    ]);

    expect($updateManager->getLogs()[0])->toContain('Test Package (1.0.0 => 2.0.0)')
        ->and($installed)->toBeArray();
});

it('completes installation correctly', function() {
    Http::fake(['https://api.tastyigniter.com/v2/core/installed' => Http::response([])]);
    app()->instance(ExtensionManager::class, $extensionManager = mock(ExtensionManager::class));
    app()->instance(ThemeManager::class, $themeManager = mock(ThemeManager::class));
    app()->instance(HubManager::class, $hubManager = mock(HubManager::class));
    $extensionManager->shouldReceive('installExtension')->with('test.extension', '2.0.0')->once();
    $themeManager->shouldReceive('installTheme')->with('test.theme', '2.0.0')->once();
    $hubManager->shouldReceive('applyInstalledItems')->once();
    $requirements = [
        PackageInfo::fromArray([
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
        ]),
        PackageInfo::fromArray([
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
        ]),
        PackageInfo::fromArray([
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
        ]),
    ];

    $updateManager = resolve(UpdateManager::class);
    $updateManager->completeInstall($requirements);
});

it('throws exception when completing installation with invalid package type', function() {
    $updateManager = resolve(UpdateManager::class);
    $requirements = [
        PackageInfo::fromArray([
            'code' => 'test.core',
            'package' => 'test/core',
            'type' => 'invalid',
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
        ]),
    ];

    expect(fn() => $updateManager->completeInstall($requirements))->toThrow(UnexpectedValueException::class);
});

function mockRequestUpdateItems()
{
    setting()->set('ignored_updates', ['igniter.ignored' => true]);
    Cache::shouldReceive('get')->with('hub_updates')->andReturn(null);
    Cache::shouldReceive('put')->once();

    $composerManager = mock(ComposerManager::class);
    app()->instance(ComposerManager::class, $composerManager);
    $composerManager->shouldReceive('listInstalledPackages')->andReturn(collect([
        [
            'name' => 'tastyigniter/core',
            'type' => 'tastyigniter-core',
            'version' => '1.0.0',
        ],
        [
            'name' => 'tastyigniter/ignored-extension',
            'type' => 'tastyigniter-package',
            'version' => '1.0.0',
            'extra' => [
                'tastyigniter-extension' => [
                    'code' => 'igniter.ignored',
                    'description' => 'Test extension description',
                    'icon' => 'fa-icon',
                    'author' => 'Sam',
                    'tags' => [],
                ],
            ],
        ],
        [
            'name' => 'tastyigniter/test-extension',
            'type' => 'tastyigniter-package',
            'version' => '1.0.0',
            'extra' => [
                'tastyigniter-extension' => [
                    'code' => 'igniter.test',
                    'description' => 'Test extension description',
                    'icon' => 'fa-icon',
                    'author' => 'Sam',
                    'tags' => [],
                ],
            ],
        ],
    ]));
    $composerManager->shouldReceive('assertSchema')->once();
    $composerManager->shouldReceive('addAuthCredentials');
    $composerManager->shouldReceive('outdated')->once()->andReturnUsing(function($callback) {
        $callback('out', json_encode(['installed' => [
            [
                'name' => 'tastyigniter/core',
                'version' => '1.0.0',
                'latest' => '1.1.0',
                'latest-status' => 'update-available',
            ],
            [
                'name' => 'tastyigniter/test-extension',
                'version' => '1.0.0',
                'latest' => '1.1.0',
                'latest-status' => 'update-available',
            ],
            [
                'name' => 'tastyigniter/ignored-extension',
                'version' => '1.0.0',
                'latest' => '1.1.0',
                'latest-status' => 'update-available',
            ],
        ]]));

        $callback('err', 'Composer running...');
    });
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
