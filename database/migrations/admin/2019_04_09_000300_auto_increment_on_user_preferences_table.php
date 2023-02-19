<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Set PRIMARY key on user_preferences table
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->dropColumn('id');
        });

        Schema::table('user_preferences', function (Blueprint $table) {
            $table->increments('id')->first();
        });
    }

    public function down()
    {
        //
    }
};
