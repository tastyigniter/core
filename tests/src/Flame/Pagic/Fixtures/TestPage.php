<?php

namespace Igniter\Tests\Flame\Pagic\Fixtures;

use Igniter\Main\Template\Page;

class TestPage extends Page
{
    public function afterBoot()
    {
        return 'booted';
    }
    
    public function afterSave()
    {
        return 'afterSave';
    }
}
