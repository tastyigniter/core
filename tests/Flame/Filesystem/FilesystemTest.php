<?php

namespace Tests;

use Igniter\Flame\Filesystem\Filesystem;
use Igniter\Flame\Igniter;

it('symbolizes path', function () {
    Igniter::loadResourcesFrom(__DIR__.'/../../_fixtures/themes/tests-theme', 'tests.fixtures');

    $path = resolve(Filesystem::class)->symbolizePath('tests.fixtures::theme.json');

    expect($path)->toEndWith('_fixtures/themes/tests-theme/theme.json');
});
