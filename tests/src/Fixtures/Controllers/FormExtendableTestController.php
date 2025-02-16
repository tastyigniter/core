<?php

declare(strict_types=1);

namespace Igniter\Tests\Fixtures\Controllers;

use Igniter\Admin\Http\Actions\FormController;

class FormExtendableTestController extends \Igniter\Admin\Classes\AdminController
{
    public array $implement = [FormController::class];

    public array $formConfig = [
        'name' => 'Test Form',
        'model' => 'Igniter\Tests\Fixtures\Models\TestModel',
        'configFile' => 'test_form',
    ];

    public function index() {}
}
