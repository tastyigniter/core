<?php

namespace Igniter\Tests\System\Database\Seeds;

use Igniter\Flame\Database\Query\Builder;
use Igniter\System\Database\Seeds\UpdateRecordsSeeder;
use Illuminate\Support\Facades\DB;

it('updates records if table is empty', function() {
    DB::shouldReceive('table')->with('media_attachments')->andReturn($mediaBuilder = mock(Builder::class));
    $mediaBuilder->shouldReceive('where')->with('disk', 'media')->andReturnSelf();
    $mediaBuilder->shouldReceive('update')->with(['disk' => 'public'])->once();

    DB::shouldReceive('table')->with('statuses')->andReturn($statusesBuilder = mock(Builder::class));
    $statusesBuilder->shouldReceive('where')->with('status_for', 'reserve')->andReturnSelf();
    $statusesBuilder->shouldReceive('exists')->andReturn(true);
    $statusesBuilder->shouldReceive('update')->with(['status_for' => 'reservation'])->once();

    (new UpdateRecordsSeeder())->run();
});
