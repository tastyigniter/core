<?php

declare(strict_types=1);

namespace Igniter\Flame\Filesystem;

use Illuminate\Support\ServiceProvider;

class FilesystemServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Filesystem::class, function() {
            $config = $this->app['config'];
            $files = new Filesystem;
            $files->filePermissions = $config->get('igniter-system.filePermissions', null);
            $files->folderPermissions = $config->get('igniter-system.folderPermissions', null);
            $files->addPathSymbol('$', public_path('vendor'));
            $files->addPathSymbol('~', base_path());

            return $files;
        });
    }
}
