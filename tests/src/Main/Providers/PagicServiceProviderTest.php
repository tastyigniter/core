<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\Providers;

it('resolves page route binding', function() {
    $result = $this->get('/components');
    $result->assertStatus(200);
    $result->assertSee('This is a test component partial content');
});
