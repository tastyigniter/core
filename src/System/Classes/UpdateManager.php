<?php

declare(strict_types=1);

namespace Igniter\System\Classes;

use Carbon\Carbon;
use Closure;
use Facades\Igniter\System\Helpers\SystemHelper;
use Igniter\Flame\Composer\Manager;
use Igniter\Flame\Database\Migrations\DatabaseMigrationRepository;
use Igniter\Flame\Database\Migrations\Migrator;
use Igniter\Flame\Exception\ApplicationException;
use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Support\Facades\Igniter;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Models\Theme;
use Igniter\System\Database\Seeds\DatabaseSeeder;
use Igniter\System\Models\Extension;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Console\Output\OutputInterface;
use UnexpectedValueException;

/**
 * TastyIgniter Updates Manager Class
 */
class UpdateManager
{
    protected array $logs = [];

    protected ?OutputInterface $logsOutput = null;

    protected array $installedItems = [];

    protected ThemeManager $themeManager;

    protected HubManager $hubManager;

    protected ExtensionManager $extensionManager;

    protected Migrator $migrator;

    protected DatabaseMigrationRepository $repository;

    protected bool $disableCoreUpdates;

    public function __construct()
    {
        $this->disableCoreUpdates = config('igniter-system.disableCoreUpdates', false);

        $this->bindContainerObjects();
    }

    public function bindContainerObjects(): void
    {
        $this->hubManager = resolve(HubManager::class);
        $this->themeManager = resolve(ThemeManager::class);
        $this->extensionManager = resolve(ExtensionManager::class);

        $this->migrator = resolve(Migrator::class);
        $this->repository = resolve(DatabaseMigrationRepository::class);
    }

    /**
     * Set the output implementation that should be used by the console.
     */
    public function setLogsOutput(OutputInterface $output): static
    {
        $this->logsOutput = $output;
        $this->migrator->setOutput($output);

        return $this;
    }

    public function log(string $message): static
    {
        if (!is_null($this->logsOutput)) {
            $this->logsOutput->writeln($message);
        }

        $this->logs[] = $message;

        return $this;
    }

    public function resetLogs(): static
    {
        $this->logs = [];

        return $this;
    }

    public function getLogs(): array
    {
        return $this->logs;
    }

    //
    //
    //

    public function down(): static
    {
        if (!$this->migrator->repositoryExists()) {
            return $this->log('<error>Migration table not found.</error>');
        }

        // Rollback extensions
        foreach (array_keys(Igniter::migrationPath()) as $code) {
            $this->purgeExtension($code);
        }

        if (!is_null($this->logsOutput)) {
            $this->migrator->setOutput($this->logsOutput);
        }

        foreach (array_reverse(Igniter::coreMigrationPath(), true) as $group => $path) {
            $this->log(sprintf('<info>Rolling back %s</info>', $group));

            $this->migrator->resetAll([$group => $path]);

            $this->log(sprintf('<info>Rolled back %s</info>', $group));
        }

        return $this;
    }

    public function migrate(): static
    {
        if (!is_null($this->logsOutput)) {
            $this->migrator->setOutput($this->logsOutput);
        }

        $this->migrator->runGroup(Igniter::coreMigrationPath());

        Model::unguarded(function() {
            resolve(DatabaseSeeder::class)->__invoke();
        });

        $this->migrator->runGroup(Igniter::migrationPath());

        return $this;
    }

    public function migrateExtension(string $name): static
    {
        if (!array_has(Igniter::migrationPath(), $name)) {
            return $this->log('<error>Unable to find migrations for:</error> '.$name);
        }

        $this->log(sprintf('<info>Migrating extension %s</info>', $name));

        if (!is_null($this->logsOutput)) {
            $this->migrator->setOutput($this->logsOutput);
        }

        $this->migrator->runGroup(array_only(Igniter::migrationPath(), $name));

        return $this;
    }

    public function purgeExtension(string $name): static
    {
        if (!array_has(Igniter::migrationPath(), $name)) {
            return $this->log('<error>Unable to find migrations for:</error> '.$name);
        }

        $this->log(sprintf('<info>Purging extension %s</info>', $name));

        if (!is_null($this->logsOutput)) {
            $this->migrator->setOutput($this->logsOutput);
        }

        $this->migrator->resetAll(array_only(Igniter::migrationPath(), $name));

        return $this;
    }

    public function rollbackExtension(string $name, array $options = []): static
    {
        if (!array_has(Igniter::migrationPath(), $name)) {
            return $this->log('<error>Unable to find migrations for:</error> '.$name);
        }

        $this->log(sprintf('<info>Rolling back extension %s</info>', $name));

        if (!is_null($this->logsOutput)) {
            $this->migrator->setOutput($this->logsOutput);
        }

        $this->migrator->rollbackAll(array_only(Igniter::migrationPath(), $name), $options);

        return $this;
    }

    //
    //
    //

    public function isLastCheckDue(): bool
    {
        $response = $this->requestUpdateList();

        return !isset($response['last_check']) || strtotime('-7 day') < strtotime((string) $response['last_check']);
    }

    public function listItems(string $itemType): array
    {
        $installedItems = $this->getInstalledItems();

        $items = $this->getHubManager()->listItems([
            'browse' => 'recommended',
            'limit' => 12,
            'type' => $itemType,
        ]);

        $installedItems = array_column($installedItems, 'name');
        if (isset($items['data'])) {
            foreach ($items['data'] as &$item) {
                $item['icon'] = generate_extension_icon($item['icon'] ?? []);
                $item['installed'] = in_array($item['code'], $installedItems);
            }
        }

        return $items;
    }

    public function searchItems(string $itemType, string $searchQuery): array
    {
        $installedItems = $this->getInstalledItems();

        $items = $this->getHubManager()->listItems([
            'type' => $itemType,
            'search' => $searchQuery,
        ]);

        $installedItems = array_column($installedItems, 'name');
        if (isset($items['data'])) {
            foreach ($items['data'] as &$item) {
                $item['icon'] = generate_extension_icon($item['icon'] ?? []);
                $item['installed'] = in_array($item['code'], $installedItems);
            }
        }

        return $items;
    }

    public function getSiteDetail(): ?array
    {
        return params('carte_info');
    }

    public function applySiteDetail(string $key): array
    {
        $manager = $this->getHubManager();
        $manager->setCarte($key);
        SystemHelper::replaceInEnv('IGNITER_CARTE_KEY=', 'IGNITER_CARTE_KEY='.$key);

        $info = [];
        $result = $manager->getDetail('site');
        if (isset($result['data']) && is_array($result['data'])) {
            $info = $result['data'];
        }

        $manager->setCarte($key, $info);

        return $info;
    }

    public function requestUpdateList(bool $force = false): array
    {
        $installedItems = $this->getInstalledItems();

        $result = $this->fetchItemsToUpdate($installedItems, $force);

        [$ignoredItems, $items] = $result['items']->filter(fn(PackageInfo $packageInfo): bool => !($packageInfo->isCore() && $this->disableCoreUpdates))->partition(fn(PackageInfo $packageInfo): bool => $this->isMarkedAsIgnored($packageInfo->code));

        $result['count'] = count($items);
        $result['items'] = $items;
        $result['ignoredItems'] = $ignoredItems;

        return $result;
    }

    public function getInstalledItems(?string $type = null): array
    {
        if ($this->installedItems) {
            return ($type && isset($this->installedItems[$type]))
                ? $this->installedItems[$type] : $this->installedItems;
        }

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

        $this->installedItems = array_collapse($installedItems);

        if (!is_null($type)) {
            return $installedItems[$type] ?? [];
        }

        return $this->installedItems;
    }

    public function requestApplyItems(array $names): Collection
    {
        return $this->getHubManager()
            ->applyItems($names)
            ->filter(function(PackageInfo $packageInfo): bool {
                if ($packageInfo->isCore() && $this->disableCoreUpdates) {
                    return false;
                }

                return !$this->isMarkedAsIgnored($packageInfo->code);
            });
    }

    public function markedAsIgnored(string $code, bool $remove = false): void
    {
        $ignoredUpdates = $this->getIgnoredUpdates();

        array_set($ignoredUpdates, $code, !$remove);

        setting()->set('ignored_updates', array_filter($ignoredUpdates));
    }

    public function getIgnoredUpdates(): array
    {
        return array_dot(setting()->get('ignored_updates') ?? []);
    }

    public function isMarkedAsIgnored(string $code): bool
    {
        return array_get($this->getIgnoredUpdates(), $code, false);
    }

    protected function fetchItemsToUpdate(array $params, bool $force = false): array
    {
        $cacheKey = 'hub_updates';

        if ($force || !$response = Cache::get($cacheKey)) {
            $response['items'] = $this->hubManager->applyItems($params, ['include' => 'tags']);
            $response['last_checked_at'] = Carbon::now()->toDateTimeString();

            Cache::put($cacheKey, $response, now()->addHours(6));
        }

        return $response;
    }

    //
    //
    //

    public function preInstall(): void
    {
        if (!SystemHelper::assertIniSet()) {
            $this->log(lang('igniter::system.updates.progress_preinstall_ok'));

            return;
        }

        $hasErrors = false;
        $errorMessage = "Please fix the following in your php.ini file before proceeding:\n\n";
        if (SystemHelper::assertIniMaxExecutionTime(120)) {
            $errorMessage .= "max_execution_time should be at least 120.\n";
            $hasErrors = true;
        }

        if (SystemHelper::assertIniMemoryLimit(1024 * 1024 * 256)) {
            $errorMessage .= "memory_limit should be at least 256M.\n";
            $hasErrors = true;
        }

        $errorMessage .= "\n".'<a href="https://tastyigniter.com/support/articles/php-ini" target="_blank">Learn how</a>';

        throw_if($hasErrors, new ApplicationException($errorMessage));
    }

    public function install(array $requirements, Closure|OutputInterface|null $output = null): void
    {
        $packages = collect($requirements)->mapWithKeys(function($package) {
            $packageInfo = $package instanceof PackageInfo ? $package : PackageInfo::fromArray($package);
            $packageName = $packageInfo->isCore() ? PackageInfo::CORE : $packageInfo->package;

            $this->log(sprintf(lang('igniter::system.updates.progress_install_version'),
                $packageInfo->name, $packageInfo->installedVersion, $packageInfo->version,
            ));

            return [$packageName => $packageInfo->version];
        })->all();

        resolve(Manager::class)->install($packages, $output);

        $this->log(lang('igniter::system.updates.progress_install_ok'));
    }

    public function completeInstall(array $requirements): void
    {
        collect($requirements)->map(fn($package): PackageInfo => $package instanceof PackageInfo ? $package : PackageInfo::fromArray($package))->each(function(PackageInfo $packageInfo) {
            match ($packageInfo->type) {
                'core' => $this->migrate(),
                'extension' => $this->extensionManager->installExtension($packageInfo->code, $packageInfo->version),
                'theme' => $this->themeManager->installTheme($packageInfo->code, $packageInfo->version),
                default => throw new UnexpectedValueException(sprintf('Unknown package type: %s', $packageInfo->type)),
            };
        });

        $this->requestUpdateList(true);

        throw new SystemException(lang('igniter::system.updates.progress_completed'));
    }

    protected function getHubManager(): HubManager
    {
        return $this->hubManager;
    }
}
