<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        $database_name = 'Tables_in_'.strtolower(DB::getDatabaseName());
        foreach (DB::select('SHOW TABLES') as $table) {
            $tableName = $table->$database_name;
            $columns = DB::select("SHOW COLUMNS FROM $tableName WHERE `Type` = 'varchar(128)'");
            foreach ($columns as $column) {
                $columnName = $column->Field;
                DB::statement("ALTER TABLE `$tableName` MODIFY COLUMN `$columnName` varchar(255)");
            }
        }
    }

    public function down()
    {
        $database_name = 'Tables_in_'.DB::getDatabaseName();
        foreach (DB::select('SHOW TABLES') as $table) {
            $tableName = $table->$database_name;
            $columns = DB::select("SHOW COLUMNS FROM $tableName WHERE `Type` = 'varchar(255)'");
            foreach ($columns as $column) {
                $columnName = $column->Field;
                DB::statement("ALTER TABLE `$tableName` MODIFY COLUMN `$columnName` varchar(128)");
            }
        }
    }
};
