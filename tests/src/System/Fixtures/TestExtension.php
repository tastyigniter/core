<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Fixtures;

use Override;
use Igniter\System\Classes\BaseExtension;

class TestExtension extends BaseExtension
{
    #[Override]
    public function register() {}
}
