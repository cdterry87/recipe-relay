<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 80);
            // A random token for the unlisted share link. Resetting it breaks old links.
            $table->string('share_token', 32)->unique();
            $table->string('summary', 280)->nullable();
            $table->string('visibility', 10)->default('private');
            $table->string('status', 10)->default('draft');
            $table->string('image_path')->nullable();
            $table->unsignedSmallInteger('prep_minutes')->nullable();
            $table->unsignedSmallInteger('cook_minutes')->nullable();
            $table->unsignedSmallInteger('total_minutes')->storedAs('coalesce(prep_minutes, 0) + coalesce(cook_minutes, 0)');
            $table->unsignedTinyInteger('servings')->nullable();
            $table->string('difficulty', 20)->nullable();
            $table->string('meal_type', 20)->nullable();
            $table->string('cuisine', 30)->nullable();
            $table->string('method', 30)->nullable();
            $table->string('occasion', 30)->nullable();
            // Lists of enum values. Filtered with JSON_CONTAINS, which keeps AND matching simple.
            $table->json('collections')->nullable();
            $table->json('dietary_tags')->nullable();
            $table->unsignedSmallInteger('calories')->nullable();
            $table->text('notes')->nullable();
            $table->string('video_url')->nullable();
            // Cached counters, kept up to date by the save and rating actions (M4).
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->unsignedInteger('saves_count')->default(0);
            $table->timestamp('published_at')->nullable();
            // Links a row to the launch seed data, so demo:reset can restore it (M6).
            $table->string('seed_key', 80)->nullable()->unique();
            $table->timestamps();

            $table->index(['visibility', 'published_at']);
            $table->index('meal_type');
            $table->fullText(['title', 'summary']);
        });

        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('section', 80)->nullable();
            $table->string('quantity', 20)->nullable();
            $table->string('unit', 30)->nullable();
            $table->string('name', 120);
            $table->string('note', 120)->nullable();
            $table->timestamps();

            $table->index(['recipe_id', 'position']);
        });

        Schema::create('steps', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->text('body');
            $table->timestamps();

            $table->index(['recipe_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('steps');
        Schema::dropIfExists('ingredients');
        Schema::dropIfExists('recipes');
    }
};
