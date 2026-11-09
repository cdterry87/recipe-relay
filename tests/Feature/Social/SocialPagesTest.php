<?php

use App\Actions\AnnounceRecipe;
use App\Actions\Cookbook;
use App\Actions\Follows;
use App\Models\Recipe;
use App\Models\User;
use Livewire\Livewire;

it('lists creators with public recipes, most followed first', function () {
    $quiet = Recipe::factory()->public()->create()->user;
    $popular = Recipe::factory()->public()->create()->user;
    $hidden = Recipe::factory()->unlisted()->create()->user;
    app(Follows::class)->follow(User::factory()->create(), $popular);

    $this->get(route('creators'))
        ->assertOk()
        ->assertSeeInOrder([$popular->name, $quiet->name])
        ->assertDontSee($hidden->name)
        ->assertSee('href="'.route('creators.show', $popular).'"', false);

    Livewire::test('pages::creators.index')
        ->set('search', $quiet->name)
        ->assertSee($quiet->name)
        ->assertDontSee($popular->name)
        ->set('search', 'zzz-nobody')
        ->assertSee('No creators');
});

it('shows a creator profile with only their public recipes', function () {
    $creator = User::factory()->create(['name' => 'Rosa Delgado', 'bio' => 'Weeknight dinners.']);
    Recipe::factory()->public()->for($creator)->create(['title' => 'Open Tacos']);
    Recipe::factory()->unlisted()->for($creator)->create(['title' => 'Secret Stew']);
    Recipe::factory()->complete()->for($creator)->create(['title' => 'Private Pie']);

    $this->get(route('creators.show', $creator))
        ->assertOk()
        ->assertSee('Rosa Delgado')
        ->assertSee('Weeknight dinners.')
        ->assertSee('Open Tacos')
        ->assertDontSee('Secret Stew')
        ->assertDontSee('Private Pie')
        ->assertSee('<strong>1</strong> recipe', false);
});

it('shows a friendly empty profile and an edit link to its owner', function () {
    $member = User::factory()->create();

    $this->get(route('creators.show', $member))->assertOk()->assertSee('Nothing on the menu');
    $this->actingAs($member)->get(route('creators.show', $member))->assertSee('Edit profile');
});

it('answers not found for unknown creators and never accepts ids', function () {
    $member = User::factory()->create();

    $this->get('/creators/'.fake()->uuid())->assertNotFound();
    $this->get('/creators/'.$member->id)->assertNotFound();
});

it('lists saved recipes in the cookbook, newest save first', function () {
    $member = User::factory()->create();
    $older = Recipe::factory()->public()->create(['title' => 'Older Save']);
    $newer = Recipe::factory()->unlisted()->create(['title' => 'Newer Save']);
    $gone = Recipe::factory()->public()->create(['title' => 'Now Private']);
    $this->travel(-2)->days();
    app(Cookbook::class)->save($member, $older);
    $this->travelBack();
    app(Cookbook::class)->save($member, $newer);
    app(Cookbook::class)->save($member, $gone);
    $gone->forceFill(['visibility' => 'private'])->save();

    $this->actingAs($member)->get(route('my.cookbook'))
        ->assertOk()
        ->assertSeeInOrder(['Newer Save', 'Older Save'])
        ->assertDontSee('Now Private')
        ->assertSee('href="'.route('recipes.shared', $newer->share_token).'"', false);
});

it('shows an empty cookbook', function () {
    $this->actingAs(User::factory()->create())->get(route('my.cookbook'))->assertSee('No saved recipes');
});

it('searches and sorts the cookbook', function () {
    $member = User::factory()->create();
    app(Cookbook::class)->save($member, Recipe::factory()->public()->create(['title' => 'Apple Pie']));
    app(Cookbook::class)->save($member, Recipe::factory()->public()->create(['title' => 'Banana Bread']));

    $this->actingAs($member);
    Livewire::test('pages::my.cookbook')
        ->set('sort', 'a-z')->assertSeeInOrder(['Apple Pie', 'Banana Bread'])
        ->set('search', 'banana')->assertSee('Banana Bread')->assertDontSee('Apple Pie');
});

it('shows the following feed and who I follow', function () {
    $member = User::factory()->create();
    $followed = Recipe::factory()->public()->create(['title' => 'From A Friend'])->user;
    Recipe::factory()->public()->create(['title' => 'From A Stranger']);
    app(Follows::class)->follow($member, $followed);

    $this->actingAs($member)->get(route('my.following'))
        ->assertOk()
        ->assertSee($followed->name)
        ->assertSee('From A Friend')
        ->assertDontSee('From A Stranger');
});

it('suggests finding creators when following nobody', function () {
    $this->actingAs(User::factory()->create())->get(route('my.following'))->assertSee('not following anyone');
});

it('lists notifications and marks them read', function () {
    $creator = Recipe::factory()->public()->create(['title' => 'Lemon Bars'])->user;
    $fan = User::factory()->create(['name' => 'Fan Person']);
    app(Follows::class)->follow($fan, $creator);
    app(Cookbook::class)->save($fan, $creator->recipes()->first());
    app(Cookbook::class)->rate($fan, $creator->recipes()->first(), 4);

    $this->actingAs($creator)->get(route('home'))->assertSee('Notifications, 2 unread');

    $this->get(route('my.notifications'))
        ->assertOk()
        ->assertSee('started following you')
        ->assertSee('gave <strong>Lemon Bars</strong> 4★', false)
        ->assertSee('is-new', false);

    expect($creator->unreadNotifications()->count())->toBe(0);
    $this->get(route('home'))->assertDontSee('unread');
});

it('shows new recipes from people I follow in notifications', function () {
    $member = User::factory()->create();
    $creator = Recipe::factory()->public()->create()->user;
    app(Follows::class)->follow($member, $creator);
    $recipe = Recipe::factory()->public()->for($creator)->create(['title' => 'Fresh Focaccia']);
    (new AnnounceRecipe)->handle($recipe);

    $this->actingAs($member)->get(route('my.notifications'))
        ->assertSee('shared a new recipe: <strong>Fresh Focaccia</strong>', false)
        ->assertSee('href="'.route('recipes.show', $recipe).'"', false);
});

it('shows an empty notifications page', function () {
    $this->actingAs(User::factory()->create())->get(route('my.notifications'))->assertSee('All quiet');
});

it('keeps the member pages behind login', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with(['my.cookbook', 'my.following', 'my.notifications']);
