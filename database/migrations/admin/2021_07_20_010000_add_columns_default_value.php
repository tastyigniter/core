<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('locationables', function(Blueprint $table) {
            $table->text('options')->change()->nullable();
        });

        Schema::table('menu_item_options', function(Blueprint $table) {
            $table->boolean('required')->change()->default(0);
        });
    }

    public function down() {}
};
