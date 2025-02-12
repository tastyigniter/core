<?php

namespace Igniter\Tests\Main\Template;

use Igniter\Main\Template\Code\PartialCode;
use Igniter\Main\Template\Partial;

it('initializes correctly', function() {
    expect(Partial::DIR_NAME)->toBe('_partials');
});

it('returns correct code class parent', function() {
    expect((new Partial)->getCodeClassParent())->toBe(PartialCode::class);
});
