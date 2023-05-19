<?php

namespace Igniter\Flame\Database\Migrations;

use Illuminate\Console\View\Components\Info;
use Illuminate\Database\Migrations\Migrator as BaseMigrator;
use Illuminate\Support\Str;

class Migrator extends BaseMigrator
{
    public function runGroup($paths = [], array $options = [])
    {
        foreach ($paths as $group => $path) {
            $this->write(Info::class, sprintf('Migrating group %s.', $group));

            $this->getRepository()->setGroup($group);
            $this->run($path, $options);
        }
    }

    public function rollbackAll($paths = [], array $options = [])
    {
        foreach ($paths as $group => $path) {
            $this->write(Info::class, sprintf('Rolling back group %s.', $group));

            $this->getRepository()->setGroup($group);
            $this->rollDown($path, $options);
        }
    }

    public function resetAll($paths = [], $pretend = false)
    {
        foreach ($paths as $group => $path) {
            $this->write(Info::class, sprintf('Resetting group %s.', $group));

            $this->getRepository()->setGroup($group);
            $this->reset((array)$path, $pretend);
        }
    }

    protected function rollDown($paths = [], array $options = [])
    {
        foreach ($paths as $group => $path) {
            $this->getRepository()->setGroup($group);

            $migrations = $this->getMigrationFiles($paths);

            $migrations = array_reverse($migrations);

            $this->requireFiles($migrations);

            if (count($migrations) === 0) {
                $this->note(Info::class, 'Nothing to rollback.');

                return;
            }

            foreach ($migrations as $migration => $file) {
                $this->runDown($file, $migration, $options['pretend'] ?? false);
            }
        }

        return $this;
    }

    /**
     * Get the name of the migration.
     *
     * @param string $path
     * @return string
     */
    public function getMigrationName($path)
    {
        if (is_null($this->getRepository()->getGroup())) {
            return parent::getMigrationName($path);
        }

        return $this->getRepository()->getGroup().'::'.str_replace('.php', '', basename($path));
    }

    /**
     * Generate a migration class name based on the migration file name.
     *
     * @param string $migrationName
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
