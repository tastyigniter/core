<?php

declare(strict_types=1);

namespace Igniter\Flame\Mail;

use Illuminate\Support\ServiceProvider;

class MailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->resolving('mail.manager', function($manager, $app) {
            $this->app['events']->dispatch('mailer.beforeRegister', [$manager]);
        });

        $this->callAfterResolving('mail.manager', function($manager, $app) {
            $this->app['events']->dispatch('mailer.register', [$this, $manager]);
        });
    }
}
