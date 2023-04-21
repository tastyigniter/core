<?php

namespace Igniter\Flame\Mixins;

use Illuminate\Support\Facades\Schema;

class BlueprintMixin
{
    public function dropForeignKeyIfExists()
    {
        return function ($key) {
            $foreignKeys = array_map(function ($key) {
                return $key->getName();
            }, Schema::getConnection()
                ->getDoctrineSchemaManager()
                ->listTableForeignKeys($this->prefix.$this->table)
            );

            if (ends_with($key, '_foreign')) {
                $key = $key;
            } else {
                $key = sprintf('%s_%s_foreign', $this->table, $key);
            }

            if (in_array($this->prefix.$key, $foreignKeys)) {
                $key = $this->prefix.$key;
            }

            if (!in_array($key, $foreignKeys)) {
                return;
            }

            return $this->dropForeign($key);
        };
    }

    public function dropIndexIfExists()
    {
        return function ($key) {
            $indexes = array_map(function ($key) {
                return $key->getName();
            }, Schema::getConnection()
                ->getDoctrineSchemaManager()
                ->listTableIndexes($this->table)
            );

            if (!starts_with($key, $this->prefix)) {
                $key = sprintf('%s%s_%s_foreign', $this->prefix, $this->table, $key);
            }

            if (!in_array($key, $indexes)) {
                return;
            }

            return $this->dropIndex($key);
        };
    }
}
