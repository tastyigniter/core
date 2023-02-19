<?php

namespace Igniter\Flame\Database\Migrations;

use Illuminate\Database\Migrations\Migrator as BaseMigrator;
use Illuminate\Support\Str;

class Migrator extends BaseMigrator
{
    public function runGroup($paths = [], array $options = [])
    {
        foreach ($paths as $group => $path) {
            $this->getRepository()->setGroup($group);
            $this->run($path, $options);
        }
    }

    public function rollbackAll($paths = [], array $options = [])
    {
        $this->notes = [];

        foreach ($paths as $group => $path) {
            $this->getRepository()->setGroup($group);

            $this->rollDown($path, $options);
        }
    }

    public function resetAll($paths = [], $pretend = false)
    {
        $this->notes = [];

        foreach ($paths as $group => $path) {
            $this->getRepository()->setGroup($group);

            $this->reset([$path], $pretend);
        }
    }

    public function rollDown($paths = [], array $options = [])
    {
        foreach ($paths as $group => $path) {
            $this->getRepository()->setGroup($group);

            $migrations = $this->getMigrationFiles($paths);

            $migrations = array_reverse($migrations);

            $this->requireFiles($migrations);

            if (count($migrations) === 0) {
                $this->note('<info>Nothing to rollback.</info>');

                return;
            }

            foreach ($migrations as $migration => $file) {
                $this->runDown($file, $migration, $options['pretend'] ?? false);
            }
        }

        return $this;
    }

    /**
     * Generate a migration class name based on the migration file name.
     *
     * @param string $migrationName
     * @return string
     */
    protected function getMigrationClass($migrationName): string
    {
        $class = Str::studly(implode('_', array_slice(explode('_', $migrationName), 4)));
        if (!class_exists($class)) {
            $className = str_replace('.', '\\', $this->getRepository()->getGroup());
            $class = $className.'\\Database\\Migrations\\'.$class;
        }

        return $class;
    }
}
