<?php

namespace Igniter\System\Providers;

use Igniter\Flame\Support\Facades\File;
use Igniter\System\Classes\MailManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class MailServiceProvider extends ServiceProvider
{
    public function register()
    {
        resolve(MailManager::class)->registerCallback(function(MailManager $manager) {
            $manager->registerMailLayouts([
                'default' => 'igniter.system::_mail.layouts.default',
            ]);

            $manager->registerMailPartials([
                'header' => 'igniter.system::_mail.partials.header',
                'footer' => 'igniter.system::_mail.partials.footer',
                'button' => 'igniter.system::_mail.partials.button',
                'panel' => 'igniter.system::_mail.partials.panel',
                'table' => 'igniter.system::_mail.partials.table',
                'subcopy' => 'igniter.system::_mail.partials.subcopy',
                'promotion' => 'igniter.system::_mail.partials.promotion',
            ]);

            $manager->registerMailVariables(
                File::getRequire(File::symbolizePath('igniter::models/system/mailvariables.php'))
            );
        });
    }

    public function boot()
    {
        Event::listen('mailer.beforeRegister', function() {
            resolve(MailManager::class)->applyMailerConfigValues();
        });
    }
}