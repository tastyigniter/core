<?php

namespace Igniter\Flame\Mixins;

use Illuminate\Support\Facades\Schema;

/** @mixin \Illuminate\Database\Schema\Blueprint */
class BlueprintMixin
{
    public function dropForeignKeyIfExists()
    {
        return function($key) {
            $foreignKeys = array_map(function($key) {
                return array_get($key, 'name');
            }, Schema::getForeignKeys($this->getTable()));

            if (ends_with($key, '_foreign')) {
                $key = $key;
            } else {
                $key = sprintf('%s_%s_foreign', $this->getTable(), $key);
            }

            if (in_array($this->getPrefix().$key, $foreignKeys)) {
                $key = $this->getPrefix().$key;
            }

            if (!in_array($key, $foreignKeys)) {
                return;
            }

            return $this->dropForeign($key);
        };
    }

    public function dropIndexIfExists()
    {
        return function($key) {
            $indexes = array_map(function($key) {
                return array_get($key, 'name');
            }, Schema::getIndexes($this->getTable()));

            if (!starts_with($key, $this->getPrefix())) {
                $key = sprintf('%s%s_%s_foreign', $this->getPrefix(), $this->getTable(), $key);
            }

            if (!in_array($key, $indexes)) {
                return;
            }

            return $this->dropIndex($key);
        };
    }
}
