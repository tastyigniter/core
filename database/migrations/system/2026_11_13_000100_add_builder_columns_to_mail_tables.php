<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('mail_templates', 'blocks')) {
            Schema::table('mail_templates', function(Blueprint $table) {
                $table->json('blocks')->nullable();
            });
        }

        if (!Schema::hasColumn('mail_layouts', 'blocks')) {
            Schema::table('mail_layouts', function(Blueprint $table) {
                $table->json('blocks')->nullable();
            });
        }

        if (!Schema::hasColumn('mail_partials', 'blocks')) {
            Schema::table('mail_partials', function(Blueprint $table) {
                $table->json('blocks')->nullable();
                $table->json('params')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('mail_templates', function(Blueprint $table) {
            $table->dropColumn('blocks');
        });

        Schema::table('mail_layouts', function(Blueprint $table) {
            $table->dropColumn('blocks');
        });

        Schema::table('mail_partials', function(Blueprint $table) {
            $table->dropColumn(['blocks', 'params']);
        });
    }
};
