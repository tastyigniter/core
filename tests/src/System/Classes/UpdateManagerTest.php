<?php

namespace Tests\System\Classes;

use Igniter\System\Classes\UpdateManager;

it('requests latest updates', function() {
    $result = resolve(UpdateManager::class)->requestUpdateList();

    expect($result)->toBeArray();
});

it('runs core database migrations', function() {
})->skip();

it('runs extension database migrations', function() {
})->skip();

it('runs core database seeders', function() {
})->skip();

it('purges extension database migrations', function() {
})->skip();

it('rollbacks extension database migrations', function() {
})->skip();
