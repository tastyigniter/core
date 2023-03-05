<?php

namespace Igniter\System\Classes;

use Carbon\Carbon;
use Composer\IO\BufferIO;
use Igniter\Flame\Exception\ApplicationException;
use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Igniter;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Models\Theme;
use Igniter\System\Helpers\SystemHelper;
use Igniter\System\Models\Country;
use Igniter\System\Models\Extension;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

/**
 * TastyIgniter Updates Manager Class
 */
class UpdateManager
{
    protected $logs = [];

    /**
     * The output interface implementation.
     *
     * @var \Illuminate\Console\OutputStyle
     */
    protected $logsOutput;

    protected $logFile;

    protected $updatedFiles;

    protected $installedItems;

    /**
     * @var ThemeManager
     */
    protected $themeManager;

    /**
     * @var HubManager
     */
    protected $hubManager;

    /**
     * @var ExtensionManager
     */
    protected $extensionManager;

    /**
     * @var \Igniter\Flame\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * @var \Igniter\Flame\Database\Migrations\DatabaseMigrationRepository
     */
    protected $repository;

    protected $disableCoreUpdates;

    public function __construct()
    {
        $this->disableCoreUpdates = config('igniter.system.disableCoreUpdates', false);

        $this->bindContainerObjects();
    }

    public function bindContainerObjects()
    {
        $this->hubManager = resolve(HubManager::class);
        $this->themeManager = resolve(ThemeManager::class);
        $this->extensionManager = resolve(ExtensionManager::class);

        $this->migrator = resolve('migrator');
        $this->repository = resolve('migration.repository');
    }

    /**
     * Set the output implementation that should be used by the console.
     *
     * @param \Illuminate\Console\OutputStyle $output
     * @return $this
     */
    public function setLogsOutput($output)
    {
        $this->logsOutput = $output;
        $this->migrator->setOutput($output);

        return $this;
    }

    public function log($message)
    {
        if (!is_null($this->logsOutput))
            $this->logsOutput->writeln($message);

        $this->logs[] = $message;

        return $this;
    }

    /**
     * @return \Igniter\System\Classes\UpdateManager $this
     */
    public function resetLogs()
    {
        $this->logs = [];

        return $this;
    }

    public function getLogs()
    {
        return $this->logs;
    }

    //
    //
    //

    public function down()
    {
        if (!$this->migrator->repositoryExists())
            return $this->log('<error>Migration table not found.</error>');

        // Rollback extensions
        foreach (array_keys(Igniter::migrationPath()) as $code) {
            $this->purgeExtension($code);
        }

        if ($this->logsOutput)
            $this->migrator->setOutput($this->logsOutput);

        foreach (array_reverse(Igniter::coreMigrationPath(), true) as $group => $path) {
            $this->log("<info>Rolling back $group</info>");

            $this->migrator->resetAll([$group => $path]);

            $this->log("<info>Rolled back $group</info>");
        }

        return $this;
    }

    public function migrate()
    {
        $this->prepareDatabase();

        $this->migrateApp();

        if (!app()->runningUnitTests()) {
            Country::upsertFromRemote();
        }

        $this->seedApp();

        foreach (array_keys(Igniter::migrationPath()) as $code) {
            $this->migrateExtension($code);
        }
    }

    protected function prepareDatabase()
    {
        $migrationTable = Config::get('database.migrations', 'migrations');

        if ($hasColumn = Schema::hasColumns($migrationTable, ['group', 'batch'])) {
            $this->repository->updateRepositoryGroup();

            $this->log('Migration table already exists');

            return true;
        }

        $this->repository->createRepository();

        $action = $hasColumn ? 'updated' : 'created';
        $this->log("Migration table {$action}");
    }

    public function migrateApp()
    {
        if ($this->logsOutput)
            $this->migrator->setOutput($this->logsOutput);

        foreach (Igniter::coreMigrationPath() as $group => $path) {
            $this->log("<info>Migrating $group</info>");

            $this->migrator->runGroup([$group => $path]);

            $this->log("<info>Migrated $group</info>");
        }

        return $this;
    }

    public function seedApp()
    {
        Artisan::call('db:seed', [
            '--class' => \Igniter\System\Database\Seeds\DatabaseSeeder::class,
            '--force' => true,
        ]);

        $this->log('<info>Seeded app</info> ');

        return $this;
    }

    public function migrateExtension($name)
    {
        if (!$this->migrator->repositoryExists())
            return $this->log('<error>Migration table not found.</error>');

        if (!$this->extensionManager->findExtension($name))
            return $this->log('<error>Unable to find:</error> '.$name);

        $this->log("<info>Migrating extension $name</info>");

        if ($this->logsOutput)
            $this->migrator->setOutput($this->logsOutput);

        $this->migrator->runGroup(array_only(Igniter::migrationPath(), $name));

        $this->log("<info>Migrated extension $name</info>");

        return $this;
    }

    public function purgeExtension($name)
    {
        if (!$this->migrator->repositoryExists())
            return $this->log('<error>Migration table not found.</error>');

        if (!$this->extensionManager->findExtension($name))
            return $this->log('<error>Unable to find:</error> '.$name);

        $this->log("<info>Purging extension $name</info>");

        if ($this->logsOutput)
            $this->migrator->setOutput($this->logsOutput);

        $this->migrator->rollDown(array_only(Igniter::migrationPath(), $name));

        $this->log("<info>Purged extension $name</info>");

        return $this;
    }

    public function rollbackExtension($name, array $options = [])
    {
        if (!$this->migrator->repositoryExists())
            return $this->log('<error>Migration table not found.</error>');

        if (!$this->extensionManager->findExtension($name))
            return $this->log('<error>Unable to find:</error> '.$name);

        if ($this->logsOutput)
            $this->migrator->setOutput($this->logsOutput);

        $this->migrator->rollbackAll(array_only(Igniter::migrationPath(), $name), $options);

        $this->log("<info>Rolled back extension $name</info>");

        return $this;
    }

    //
    //
    //

    public function isLastCheckDue()
    {
        $response = $this->requestUpdateList();

        if (isset($response['last_check'])) {
            return strtotime('-7 day') < strtotime($response['last_check']);
        }

        return true;
    }

    public function listItems($itemType)
    {
        $installedItems = $this->getInstalledItems();

        $items = $this->getHubManager()->listItems([
            'browse' => 'recommended',
            'limit' => 12,
            'type' => $itemType,
        ]);

        $installedItems = array_column($installedItems, 'name');
        if (isset($items['data'])) foreach ($items['data'] as &$item) {
            $item['icon'] = generate_extension_icon($item['icon'] ?? []);
            $item['installed'] = in_array($item['code'], $installedItems);
        }

        return $items;
    }

    public function searchItems($itemType, $searchQuery)
    {
        $installedItems = $this->getInstalledItems();

        $items = $this->getHubManager()->listItems([
            'type' => $itemType,
            'search' => $searchQuery,
        ]);

        $installedItems = array_column($installedItems, 'name');
        if (isset($items['data'])) foreach ($items['data'] as &$item) {
            $item['icon'] = generate_extension_icon($item['icon'] ?? []);
            $item['installed'] = in_array($item['code'], $installedItems);
        }

        return $items;
    }

    public function getSiteDetail()
    {
        return params('carte_info');
    }

    public function applySiteDetail($key)
    {
        SystemHelper::replaceInEnv('IGNITER_CARTE_KEY=', 'IGNITER_CARTE_KEY='.$key);

        $info = [];
        $result = $this->getHubManager()->getDetail('site');
        if (isset($result['data']) && is_array($result['data']))
            $info = $result['data'];

        params()->set('carte_info', $info);
        params()->save();

        return $info;
    }

    public function requestUpdateList($force = false)
    {
        $installedItems = $this->getInstalledItems();

        $result = $this->fetchItemsToUpdate($installedItems, $force);
        if (!is_array($result))
            return $result;

        [$ignoredItems, $items] = $result['items']->filter(function (PackageInfo $packageInfo) {
            return !($packageInfo->isCore() && $this->disableCoreUpdates);
        })->partition(function (PackageInfo $packageInfo) {
            return $this->isMarkedAsIgnored($packageInfo->code);
        });

        $result['count'] = count($items);
        $result['items'] = $items;
        $result['ignoredItems'] = $ignoredItems;

        return $result;
    }

    public function getInstalledItems($type = null)
    {
        if ($this->installedItems)
            return ($type && isset($this->installedItems[$type]))
                ? $this->installedItems[$type] : $this->installedItems;

        $installedItems = [];

        $extensionVersions = Extension::pluck('version', 'name');
        foreach ($extensionVersions as $code => $version) {
            $installedItems['extensions'][] = [
                'name' => $code,
                'ver' => $version,
                'type' => 'extension',
            ];
        }

        $themeVersions = Theme::pluck('version', 'code');
        foreach ($themeVersions as $code => $version) {
            $installedItems['themes'][] = [
                'name' => $code,
                'ver' => $version,
                'type' => 'theme',
            ];
        }

        if (!is_null($type))
            return $installedItems[$type] ?? [];

        return $this->installedItems = array_collapse($installedItems);
    }

    public function requestApplyItems($names): Collection
    {
        return $this->getHubManager()
            ->applyItems($names)
            ->filter(function (PackageInfo $packageInfo) {
                if ($packageInfo->isCore() && $this->disableCoreUpdates)
                    return false;

                return !$this->isMarkedAsIgnored($packageInfo->code);
            });
    }

    public function markedAsIgnored(string $code, bool $remove = false)
    {
        $ignoredUpdates = $this->getIgnoredUpdates();

        array_set($ignoredUpdates, $code, !$remove);

        setting()->set('ignored_updates', array_filter($ignoredUpdates));
    }

    public function getIgnoredUpdates()
    {
        return array_dot(setting()->get('ignored_updates') ?? []);
    }

    public function isMarkedAsIgnored($code)
    {
        return array_get($this->getIgnoredUpdates(), $code, false);
    }

    protected function fetchItemsToUpdate($params, $force = false): array
    {
        $cacheKey = 'hub_updates';

        if ($force || !$response = Cache::get($cacheKey)) {
            $response['items'] = $this->hubManager->applyItems($params, ['include' => 'tags']);
            $response['last_checked_at'] = Carbon::now()->toDateTimeString();

            Cache::put($cacheKey, $response, now()->addHours(3));
        }

        return $response;
    }

    //
    //
    //

    public function preInstall()
    {
        if (SystemHelper::assertIniSet())
            return;

        $hasErrors = false;
        $errorMessage = "Please fix the following in your php.ini file before proceeding:\n\n";
        if (SystemHelper::assertIniMaxExecutionTime(120)) {
            $errorMessage .= "max_execution_time should be at least 120.\n";
            $hasErrors = true;
        }

        if (!SystemHelper::assertIniMemoryLimit(1024 * 1024 * 256)) {
            $errorMessage .= "memory_limit should be at least 256M.\n";
            $hasErrors = true;
        }

        $errorMessage .= "\n".'<a href="https://tastyigniter.com/support/articles/php-ini" target="_blank">Learn how</a>';

        throw_if($hasErrors, new ApplicationException($errorMessage));
    }

    /**
     * @throws \Igniter\Flame\Exception\ComposerException
     */
    public function install(array $requirements)
    {
        $io = new BufferIO();

        $packages = collect($requirements)->mapWithKeys(function ($package) {
            $packageInfo = $package instanceof PackageInfo ? $package : PackageInfo::fromArray($package);
            $packageName = $packageInfo->isCore() ? 'tastyigniter/core' : $packageInfo->package;

            $this->log(sprintf(lang('igniter::system.updates.progress_install_version'),
                $packageInfo->name, $packageInfo->installedVersion, $packageInfo->version
            ));

            return [$packageName => $packageInfo->version];
        })->all();

        resolve(ComposerManager::class)->install($packages, $io);

        $this->log(lang('igniter::system.updates.progress_install_ok')."\nOutput: ".$io->getOutput());
    }

    public function completeInstall($requirements)
    {
        collect($requirements)->map(function ($package) {
            return $package instanceof PackageInfo ? $package : PackageInfo::fromArray($package);
        })->each(function (PackageInfo $packageInfo) {
            match ($packageInfo->type) {
                'core' => function () use ($packageInfo) {
                    $this->migrate();
                },
                'extension' => function () use ($packageInfo) {
                    $this->extensionManager->installExtension($packageInfo->code, $packageInfo->version);
                },
                'theme' => function () use ($packageInfo) {
                    $this->themeManager->installTheme($packageInfo->code, $packageInfo->version);
                },
                default => null,
            };
        });

        $this->requestUpdateList(true);

        throw new SystemException(lang('igniter::system.updates.progress_completed'));
    }

    /**
     * @return \Igniter\System\Classes\HubManager
     */
    protected function getHubManager()
    {
        return $this->hubManager;
    }
}
