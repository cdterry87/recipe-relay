<?php

use App\Actions\Cookbook;
use App\Actions\DeleteAccount;
use App\Actions\Follows;
use App\Actions\SaveRecipe;
use App\Enums\Visibility;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Storage;

it('removes a deleted member from counters, follows and notifications', function () {
    $recipe = Recipe::factory()->public()->create();
    $member = User::factory()->create();
    app(Cookbook::class)->save($member, $recipe);
    app(Cookbook::class)->rate($member, $recipe, 5);
    app(Follows::class)->follow($member, $recipe->user);
    app(Follows::class)->follow($recipe->user, Recipe::factory()->public()->for($member)->create()->user);

    (new DeleteAccount)->handle($member);

    $recipe->refresh();
    expect($recipe->saves_count)->toBe(0)
        ->and($recipe->rating_count)->toBe(0)
        ->and($recipe->user->followers()->count())->toBe(0)
        ->and($recipe->user->following()->count())->toBe(0)
        ->and(DatabaseNotification::query()->whereMorphedTo('notifiable', $member)->count())->toBe(0);
});

it('deletes the member\'s recipe photos', function () {
    Storage::fake('public');
    $member = User::factory()->create();
    $recipe = app(SaveRecipe::class)->handle(
        $member, null, ['title' => 'Pie'], [['name' => 'Flour']], [['body' => 'Bake.']],
        Visibility::Private, UploadedFile::fake()->image('pie.jpg', 1200, 900),
    );
    Storage::disk('public')->assertExists($recipe->image_path.'-800.webp');

    (new DeleteAccount)->handle($member);

    Storage::disk('public')->assertMissing($recipe->image_path.'-800.webp');
});
