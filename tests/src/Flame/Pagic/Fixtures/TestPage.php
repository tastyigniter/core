<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Pagic\Fixtures;

use Igniter\Main\Template\Page;

class TestPage extends Page
{
    public function afterBoot(): string
    {
        return 'booted';
    }

    public function afterSave(): string
    {
        return 'afterSave';
    }
}
