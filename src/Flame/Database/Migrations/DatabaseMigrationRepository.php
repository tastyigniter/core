<?php

namespace Igniter\Flame\Database\Migrations;

use Illuminate\Database\Migrations\DatabaseMigrationRepository as BaseDatabaseMigrationRepository;
use Illuminate\Database\Schema\Blueprint;

class DatabaseMigrationRepository extends BaseDatabaseMigrationRepository
{
    protected $group;

    /**
     * Get the ran migrations.
     * @return array
     */
    public function getRan()
    {
        return $this->table()
            ->orderBy('batch', 'asc')
            ->orderBy('migration', 'asc')
            ->pluck('migration')->all();
    }

    /**
     * Log that a migration was run.
     * Overrides the parent method and allows insertion of group data
     *
     * @param string $file
     * @param int $batch
     *
     * @return void
     */
    public function log($file, $batch)
    {
        $record = ['migration' => $file, 'group' => $this->getGroup(), 'batch' => $batch];

        $this->table()->insert($record);
    }

    /**
     * Create the migration repository data store.
     * @return void
     */
    public function createRepository()
    {
        parent::createRepository();

        $schema = $this->getConnection()->getSchemaBuilder();

        // Drop old columns from CI_Migration library
        if ($schema->hasColumns($this->table, ['type', 'version'])) {
            $schema->table($this->table, function (Blueprint $table) {
                $table->dropColumn('type');
                $table->dropColumn('version');
            });
        }

        $schema->table($this->table, function (Blueprint $table) {
            $table->string('group')->nullable();
        });
    }

    public function updateRepositoryGroup()
    {
        if ($this->getConnection()->table($this->table)->where('group', 'igniter.system')->exists())
            return;

        $this->getConnection()->getSchemaBuilder()->table($this->table, function (Blueprint $table) {
            $table->string('group')->nullable()->change();
        });

        $this->getConnection()
            ->table($this->table)
            ->where('group', 'admin')
            ->update(['group' => 'igniter.admin']);

        $this->getConnection()
            ->table($this->table)
            ->where('group', 'system')
            ->update(['group' => 'igniter.system']);
    }

    /**
     * Get a query builder for the migration table.
     * @return \Illuminate\Database\Query\Builder
     */
    protected function table()
    {
        return $this->getConnection()
            ->table($this->table)
            ->where('group', $this->getGroup())
            ->useWritePdo();
    }

    /**
     * Remove a migration from the log.
     *
     * @param object $migration
     *
     * @return void
     */
    public function delete($migration)
    {
        if (!is_string($migration))
            $migration = $migration->migration;

        $this->table()->where('migration', $migration)->delete();
    }

    /**
     * Get the module or extension the migration belongs to.
     *
     * @return string
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Set the module or extension the migration belongs to.
     *
     * @param string $name
     *
     * @return void
     */
    public function setGroup($name)
    {
        $this->group = $name;
    }
}
