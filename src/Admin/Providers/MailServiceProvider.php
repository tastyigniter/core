<?php

namespace Igniter\Admin\Providers;

use Igniter\System\Classes\MailManager;
use Illuminate\Support\ServiceProvider;

class MailServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerMailTemplates();
    }

    public function boot()
    {
    }

    protected function registerMailTemplates()
    {
        resolve(MailManager::class)->registerCallback(function (MailManager $manager) {
            $manager->registerMailTemplates([
                'igniter.admin::_mail.order_update' => 'lang:igniter::system.mail_templates.text_order_update',
                'igniter.admin::_mail.reservation_update' => 'lang:igniter::system.mail_templates.text_reservation_update',
                'igniter.admin::_mail.password_reset' => 'lang:igniter::system.mail_templates.text_password_reset_alert',
                'igniter.admin::_mail.password_reset_request' => 'lang:igniter::system.mail_templates.text_password_reset_request_alert',
                'igniter.admin::_mail.invite' => 'lang:igniter::system.mail_templates.text_invite',
                'igniter.admin::_mail.invite_customer' => 'lang:igniter::system.mail_templates.text_invite_customer',
                'igniter.admin::_mail.low_stock_alert' => 'lang:igniter::system.mail_templates.text_low_stock_alert',
            ]);
        });
    }
}