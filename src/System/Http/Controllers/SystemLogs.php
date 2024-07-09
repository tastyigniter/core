<?php

namespace Igniter\System\Http\Controllers;

use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Facades\Template;
use Igniter\Flame\Support\Facades\File;
use Igniter\Flame\Support\LogViewer;

class SystemLogs extends \Igniter\Admin\Classes\AdminController
{
    protected null|string|array $requiredPermissions = 'Admin.SystemLogs';

    protected string $logFile = '/logs/laravel';

    public function index()
    {
        AdminMenu::setContext('system_logs', 'system');

        Template::setTitle(lang('igniter::system.system_logs.text_title'));
        Template::setHeading(lang('igniter::system.system_logs.text_title'));
        Template::setButton(lang('igniter::admin.button_refresh'), [
            'class' => 'btn btn-primary',
            'href' => 'system_logs',
        ]);
        Template::setButton(lang('igniter::system.system_logs.button_empty'), [
            'class' => 'btn btn-link pull-right text-danger text-decoration-none',
            'data-request-form' => '#lists-list-form',
            'data-request' => 'onEmptyLog',
            'data-request-confirm' => lang('igniter::admin.alert_warning_confirm'),
        ]);
        Template::setButton(lang('igniter::system.system_logs.button_request_logs'), [
            'class' => 'btn btn-default',
            'href' => 'request_logs',
        ]);

        $logFile = $this->getLogsFile();

        $logs = [];
        if (File::exists($logFile)) {
            LogViewer::setFile($logFile);
            $logs = LogViewer::all() ?? [];
        }

        $this->vars['logs'] = $logs;
    }

    public function index_onEmptyLog()
    {
        $logFile = $this->getLogsFile();
        if (File::exists($logFile) && File::isWritable($logFile)) {
            File::put($logFile, '');

            flash()->success(sprintf(lang('igniter::admin.alert_success'), 'Logs Emptied '));
        }

        return $this->redirectBack();
    }

    /**
     * Get the path to the logs file
     */
    protected function getLogsFile(): string
    {
        // default daily rotating logs (Laravel 5.0)
        $path = storage_path($this->logFile.'-'.date('Y-m-d').'.log');

        // single file logs
        if (!file_exists($path)) {
            $path = storage_path($this->logFile.'.log');
        }

        return $path;
    }
}
