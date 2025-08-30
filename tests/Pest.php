<?php

declare(strict_types=1);

use Igniter\Tests\TestCase;
use Igniter\User\Models\User;

uses(TestCase::class)->in(__DIR__.'/src');

function testThemePath()
{
    return realpath(__DIR__.'/resources/themes/tests-theme');
}

function actingAsSuperUser()
{
    return test()->actingAs(User::factory()->superUser()->create(), 'igniter-admin');
}
