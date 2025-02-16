<?php

declare(strict_types=1);

use Igniter\User\Models\User;

uses(Igniter\Tests\TestCase::class)->in(__DIR__.'/src');

pest()->group('admin')->in('src/Admin');
pest()->group('main')->in('src/Main');
pest()->group('system')->in('src/System');
pest()->group('flame')->in('src/Flame');

function testThemePath()
{
    return realpath(__DIR__.'/resources/themes/tests-theme');
}

function actingAsSuperUser()
{
    return test()->actingAs(User::factory()->superUser()->create(), 'igniter-admin');
}
