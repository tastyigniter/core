<?php

declare(strict_types=1);

namespace Igniter\Tests\Fixtures\Controllers;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Http\Actions\FormController;
use Igniter\Tests\Fixtures\Models\TestModel;

class FormExtendableTestController extends AdminController
{
    public array $implement = [FormController::class];

    public array $formConfig = [
        'name' => 'Test Form',
        'model' => TestModel::class,
        'configFile' => 'test_form',
    ];

    public function index() {}
}
