<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Gives a model an ordered UUID in its `uuid` column, alongside the auto-increment `id`.
 *
 * `id` stays the primary key for foreign keys and joins. The UUID is the only identifier
 * that leaves the server: routes bind on it, and it is what pages, Livewire actions and
 * links carry. See section B5.1 of docs/spec.md.
 */
trait HasPublicUuid
{
    use HasUuids;

    /**
     * The columns that receive a UUID when the model is created.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
