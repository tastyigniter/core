<?php

namespace Igniter\Tests\Main\Http\Controllers;

it('loads media manager page', function() {
    actingAsSuperUser()
        ->get(route('igniter.main.media_manager'))
        ->assertStatus(200)
        ->assertSee('Media Manager');
});
