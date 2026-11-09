<?php

namespace App\Enums\Concerns;

/**
 * Shared helpers for backed enums whose value doubles as the stored value.
 */
trait HasOptions
{
    public function label(): string
    {
        return $this->value;
    }

    /**
     * Value => label pairs, in declaration order, for selects and checkbox lists.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
