<?php

namespace Igniter\Flame\Filesystem;

use Igniter\Igniter;
use Illuminate\Filesystem\FilesystemServiceProvider as BaseFilesystemServiceProvider;

/**
 * Class FilesystemServiceProvider
 */
class FilesystemServiceProvider extends BaseFilesystemServiceProvider
{
    /**
     * Register the native filesystem implementation.
     * @return void
     */
    protected function registerNativeFilesystem()
    {
        $this->app->singleton('files', function () {
            $config = $this->app['config'];
            $files = new Filesystem;
            $files->filePermissions = $config->get('system.filePermissions', null);
            $files->folderPermissions = $config->get('system.folderPermissions', null);
            $files->pathSymbols = [
                '@' => Igniter::resourcesPath(),
                '$' => public_path('vendor/igniter/admin'),
                '~' => base_path(),
            ];

            return $files;
        });
    }

    /**
     * Get the services provided by the provider.
     * @return array
     */
    public function provides()
    {
        return ['files', 'filesystem'];
    }
}
