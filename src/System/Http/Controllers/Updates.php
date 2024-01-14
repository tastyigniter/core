<?php

namespace Igniter\System\Http\Controllers;

use Exception;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Facades\Template;
use Igniter\Flame\Igniter;
use Igniter\Main\Models\Theme;
use Igniter\System\Classes\UpdateManager;
use Igniter\System\Models\Extension;
use Igniter\System\Traits\ManagesUpdates;

class Updates extends \Igniter\Admin\Classes\AdminController
{
    use ManagesUpdates;

    public string $checkUrl = 'updates';

    public string $browseUrl = 'updates/browse';

    protected null|string|array $requiredPermissions = 'Site.Updates';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('updates', 'system');
    }

    public function index()
    {
        Extension::syncAll();
        Theme::syncAll();

        $pageTitle = lang('igniter::system.updates.text_title');
        Template::setTitle($pageTitle);
        Template::setHeading($pageTitle);

        $this->prepareAssets();

        try {
            $updateManager = resolve(UpdateManager::class);
            $this->vars['igniterVersion'] = Igniter::version();
            $this->vars['carteInfo'] = $updateManager->getSiteDetail();
            $this->vars['updates'] = $updateManager->requestUpdateList();
        } catch (Exception $ex) {
            flash()->warning($ex->getMessage())->now();
        }
    }
}
