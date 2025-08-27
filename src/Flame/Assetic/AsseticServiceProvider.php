<?php

declare(strict_types=1);

namespace Igniter\Flame\Assetic;

use Override;
use Illuminate\Support\ServiceProvider;

class AsseticServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->singleton(AssetManager::class);
    }
}
