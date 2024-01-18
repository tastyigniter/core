<?php

uses(Tests\TestCase::class)->in(__DIR__.'/src');

function testThemePath()
{
    return realpath(__DIR__.'/resources/themes/tests-theme');
}
