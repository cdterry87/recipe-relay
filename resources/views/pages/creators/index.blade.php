<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Every creator with a public recipe, most followed first.
 */
new #[Layout('layouts::app'), Title('Creators')] class extends Component
{
    public const PER_PAGE = 24;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public int $shown = self::PER_PAGE;

    public function updatedSearch(): void
    {
        $this->shown = self::PER_PAGE;
    }

    /**
     * @return Builder<User>
     */
    private function query(): Builder
    {
        $term = trim(mb_substr($this->search, 0, 60));

        return User::query()->creators()
            ->when($term !== '', fn ($q) => $q->where('name', 'like', '%'.addcslashes($term, '\\%_').'%'));
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function creators(): Collection
    {
        return $this->query()->trending()->limit($this->shown)->get();
    }

    #[Computed]
    public function total(): int
    {
        return $this->query()->count();
    }

    public function loadMore(): void
    {
        $this->shown = min($this->shown + self::PER_PAGE, 500);
    }
};
?>

<div class="stack stack--lg">
    <header class="section__header">
        <p class="eyebrow">Cooks worth following</p>
        <h1>Meet the <em>creators</em></h1>
        <p class="muted">Follow the cooks you like and their new recipes land in your feed and your weekly digest.</p>
    </header>

    <div class="search-field creators-search">
        <label class="visually-hidden" for="creator-search">Search creators</label>
        <x-ui.icon name="search" class="search-field__icon" />
        <input class="search-field__input" id="creator-search" type="search" maxlength="60" autocomplete="off" placeholder="Search creators by name" wire:model.live.debounce.300ms="search">
    </div>

    @if ($this->total === 0)
        <section class="empty-state">
            <span class="empty-state__art" aria-hidden="true">👩‍🍳</span>
            @if ($search !== '')
                <h2>No creators <em>match</em></h2>
                <p class="muted">Try a different name.</p>
            @else
                <h2>The kitchen is <em>quiet</em></h2>
                <p class="muted">Creators appear here once they share a public recipe.</p>
            @endif
        </section>
    @else
        <div class="creator-grid">
            @foreach ($this->creators as $creator)
                <x-creator.tile :creator="$creator" wire:key="creator-{{ $creator->uuid }}" />
            @endforeach
        </div>
        @if ($this->total > $this->creators->count())
            <div class="load-more">
                <x-ui.button variant="secondary" wire:click="loadMore" wire:loading.attr="disabled">Show more</x-ui.button>
            </div>
        @endif
    @endif
</div>
