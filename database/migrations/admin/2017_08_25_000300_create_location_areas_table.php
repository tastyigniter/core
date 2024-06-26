<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create location_areas table
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('location_areas', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('area_id');
            $table->integer('location_id');
            $table->string('name');
            $table->string('type', 32);
            $table->text('boundaries');
            $table->text('conditions');
        });
    }

    public function down()
    {
        Schema::dropIfExists('location_areas');
    }
};
