<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Mixins;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('drops foreign key if it exists', function() {
    Schema::shouldReceive('getForeignKeys')->with('table_name')->andReturn([
        ['name' => 'table_name_column_foreign'],
    ]);

    $blueprint = new Blueprint(DB::connection(), 'table_name');
    $blueprint->dropForeignKeyIfExists('column');

    $command = $blueprint->getCommands()[0]->toArray();
    expect($command)->toHaveKey('name', 'dropForeign')
        ->and($command)->toHaveKey('index', 'table_name_column_foreign');
});

it('does not drop foreign key if it does not exist', function() {
    Schema::shouldReceive('getForeignKeys')->with('table_name')->andReturn([]);

    $blueprint = new Blueprint(DB::connection(), 'table_name');
    $blueprint->dropForeignKeyIfExists('column');

    expect($blueprint->getCommands())->not->toContain('dropForeign');
    $blueprint->dropForeignKeyIfExists('column_foreign');
    expect($blueprint->getCommands())->not->toContain('dropForeign');
});

it('drops index if it exists', function() {
    Schema::shouldReceive('getIndexes')->with('table_name')->andReturn([
        ['name' => 'table_name_column_index'],
    ]);

    $blueprint = new Blueprint(DB::connection(), 'table_name');
    $blueprint->dropIndexIfExists('column');

    $command = $blueprint->getCommands()[0]->toArray();
    expect($command)->toHaveKey('name', 'dropIndex')
        ->and($command)->toHaveKey('index', 'table_name_column_index');
});

it('does not drop index if it does not exist', function() {
    Schema::shouldReceive('getIndexes')->with('table_name')->andReturn([]);

    $blueprint = new Blueprint(DB::connection(), 'table_name');
    $blueprint->dropIndexIfExists('column');

    expect($blueprint->getCommands())->not->toContain('dropIndex');
});
