<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Fixtures;

use Igniter\System\Classes\BaseExtension;
use Override;

class TestExtension extends BaseExtension
{
    #[Override]
    public function register() {}
}
