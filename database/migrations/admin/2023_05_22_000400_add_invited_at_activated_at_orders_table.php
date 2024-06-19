<?php

namespace Igniter\Admin\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('admin_users', function(Blueprint $table) {
            $table->timestamp('invited_at')->nullable();
            $table->renameColumn('date_activated', 'activated_at')->nullable();
        });

        Schema::table('customers', function(Blueprint $table) {
            $table->timestamp('invited_at')->nullable();
            $table->renameColumn('date_activated', 'activated_at')->nullable();
        });
    }

    public function down() {}
};
