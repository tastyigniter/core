<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('working_hours', function(Blueprint $table) {
            $table->bigIncrements('id');
        });
    }

    public function down() {}
};
