<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Search uses LIKE matching across several tables (see RecipeFilters), so the unused
 * FULLTEXT index only slowed down writes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropFullText(['title', 'summary']);
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->fullText(['title', 'summary']);
        });
    }
};
