<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('theme_page_layouts')) {
            return;
        }

        Schema::create('theme_page_layouts', function(Blueprint $table) {
            $table->id();
            $table->string('theme_code');
            $table->string('page_code');
            $table->json('sections')->nullable();
            $table->timestamps();

            $table->unique(['theme_code', 'page_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_page_layouts');
    }
};
