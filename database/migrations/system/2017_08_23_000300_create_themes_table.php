<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create themes table
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('themes', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->integer('theme_id', true);
            $table->string('name');
            $table->string('code', 128)->unique();
            $table->text('description')->nullable();
            $table->string('version')->nullable()->default('0.0.1');
            $table->text('data')->nullable();
            $table->boolean('status')->default(0);
            $table->boolean('is_default')->default(0);
        });
    }

    public function down()
    {
        Schema::dropIfExists('themes');
    }
};
