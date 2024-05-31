<?php

namespace Igniter\Tests\System\Models;

use Igniter\System\Models\MailTheme;

it('compiles theme default css file', function() {
    expect(MailTheme::compileCss())->toBeString();
});
