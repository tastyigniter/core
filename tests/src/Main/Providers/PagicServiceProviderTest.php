<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\Providers;

use Igniter\Main\Http\Middleware\CheckInitialSetup;

it('resolves page route binding', function() {
    $result = $this->withoutMiddleware(CheckInitialSetup::class)->get('/components');
    $result->assertStatus(200);
    $result->assertSee('This is a test component partial content');
});
