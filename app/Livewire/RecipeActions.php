<?php

namespace App\Livewire;

use App\Actions\Cookbook;
use App\Livewire\Concerns\RemembersGuestIntent;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The Save button and star rating on a recipe page. The component holds the recipe's UUID,
 * not the model, so no database id reaches the page.
 *
 * @property-read Recipe $recipe
 * @property-read bool $saved
 * @property-read int|null $myRating
 */
class RecipeActions extends Component
{
    use RemembersGuestIntent;

    #[Locked]
    public string $recipeUuid = '';

    #[Locked]
    public string $returnUrl = '';

    public function mount(Recipe $recipe): void
    {
        $this->recipeUuid = $recipe->uuid;
        $this->returnUrl = url()->current();

        if ($this->pendingIntent('save', $recipe->uuid) && Gate::allows('save', $recipe) && ! $this->saved) {
            app(Cookbook::class)->save($this->user(), $recipe);
            unset($this->saved, $this->recipe);
            $this->dispatch('toast', type: 'success', message: 'Saved to your cookbook. You can rate it now.');
        }
    }

    #[Computed]
    public function recipe(): Recipe
    {
        return Recipe::query()->with('user')->where('uuid', $this->recipeUuid)->firstOrFail();
    }

    #[Computed]
    public function saved(): bool
    {
        return auth()->check() && $this->user()->hasSaved($this->recipe);
    }

    #[Computed]
    public function myRating(): ?int
    {
        return auth()->check() ? $this->user()->ratingFor($this->recipe) : null;
    }

    public function toggleSave(Cookbook $cookbook): void
    {
        if (auth()->guest()) {
            $this->rememberAndLogIn('save', $this->recipeUuid, $this->returnUrl);

            return;
        }

        if ($this->saved) {
            $cookbook->remove($this->user(), $this->recipe);
            $message = 'Removed from your cookbook.';
        } else {
            Gate::authorize('save', $this->recipe);
            $cookbook->save($this->user(), $this->recipe);
            $message = 'Saved to your cookbook.';
        }

        unset($this->saved, $this->myRating, $this->recipe);
        $this->dispatch('toast', type: 'success', message: $message);
    }

    public function rate(int $stars, Cookbook $cookbook): void
    {
        abort_if(auth()->guest(), 403);

        if ($stars < 1 || $stars > 5) {
            return;
        }

        $response = Gate::inspect('rate', $this->recipe);

        if ($response->denied()) {
            $this->dispatch('toast', type: 'info', message: (string) $response->message());

            return;
        }

        $cookbook->rate($this->user(), $this->recipe, $stars);

        unset($this->myRating, $this->recipe);
        $this->dispatch('toast', type: 'success', message: "Thanks! You gave it {$stars} ".str('star')->plural($stars).'.');
    }

    public function render(): View
    {
        return view('livewire.recipe-actions');
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
