<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Database;

use Igniter\Flame\Database\Pivot;
use Igniter\System\Models\Page;
use Illuminate\Database\Eloquent\Builder;

it('sets keys for save query correctly', function() {
    $parent = new Page;
    $pivot = (new class extends Pivot
    {
        public function testSetKeysForSaveQuery($query)
        {
            return $this->setKeysForSaveQuery($query);
        }
    })::fromRawAttributes($parent, ['foreign_key' => 1, 'other_key' => 2], 'pivot_table', true);
    $pivot->setPivotKeys('foreign_key', 'other_key');

    $query = new Builder($parent->newQuery()->getQuery());
    $result = $pivot->testSetKeysForSaveQuery($query);

    expect($result->toSql())->toContain('where `foreign_key` = ? and `other_key` = ?')
        ->and($pivot->getForeignKey())->toBe('foreign_key')
        ->and($pivot->getOtherKey())->toBe('other_key')
        ->and($pivot->getCreatedAtColumn())->toBe('created_at')
        ->and($pivot->getUpdatedAtColumn())->toBe('updated_at');
});

it('deletes pivot model record from database', function() {
    $parent = new Page;
    $pivot = (new class extends Pivot {})::fromRawAttributes($parent, ['foreign_key' => 1, 'other_key' => 2], 'pivot_table', true);
    $pivot->setPivotKeys('foreign_key', 'other_key');

    expect(fn() => $pivot->delete())->toThrow('delete from `pivot_table`');
});
