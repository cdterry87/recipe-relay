<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A recipe in a member's cookbook. Rating requires one (see App\Actions\Cookbook).
        Schema::create('saves', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'recipe_id']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('stars');
            $table->timestamps();

            $table->unique(['user_id', 'recipe_id']);
        });

        // One-way: the follower sees the followee's new public recipes.
        Schema::create('follows', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('follower_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('followee_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['follower_id', 'followee_id']);
            $table->index('followee_id');
        });

        Schema::table('recipes', function (Blueprint $table) {
            // Set the first time the recipe goes public, when followers are told about it.
            // Later edits or visibility changes never announce it again.
            $table->timestamp('announced_at')->nullable()->after('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('recipes', fn (Blueprint $table) => $table->dropColumn('announced_at'));
        Schema::dropIfExists('follows');
        Schema::dropIfExists('ratings');
        Schema::dropIfExists('saves');
    }
};
