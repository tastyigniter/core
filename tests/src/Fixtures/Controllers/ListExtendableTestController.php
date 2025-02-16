<?php

declare(strict_types=1);

namespace Igniter\Tests\Fixtures\Controllers;

use Igniter\Admin\Http\Actions\ListController;
use Igniter\Admin\Models\Status;

class ListExtendableTestController extends \Igniter\Admin\Classes\AdminController
{
    public array $implement = [ListController::class];

    public array $listConfig = [
        'list' => [
            'model' => Status::class,
            'configFile' => 'test_list',
        ],
    ];

    public function index() {}
}
