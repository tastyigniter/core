<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('stocks', function(Blueprint $table) {
            $table->boolean('low_stock_alert_sent')->default(0);
        });
    }

    public function down()
    {
    }
};
