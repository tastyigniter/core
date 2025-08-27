<?php

declare(strict_types=1);

namespace Igniter\Tests\Fixtures\Widgets;

use Igniter\Admin\Classes\BaseFormWidget;
use Igniter\Admin\Classes\FormField;
use Override;

class TestFormWidget extends BaseFormWidget
{
    public string $property = 'value';

    #[Override]
    public function initialize(): void
    {
        $this->fillFromConfig([
            'property',
        ]);
    }

    public function getFormField(): FormField
    {
        return $this->formField;
    }
}
