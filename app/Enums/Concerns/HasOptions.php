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

    /**
     * The URL-friendly name, such as "quick-and-easy" or "gluten-free".
     */
    public function slug(): string
    {
        return str($this->value)->replace('&', 'and')->slug()->value();
    }

    /**
     * The case for a URL name, or null for anything unknown.
     */
    public static function fromSlug(?string $slug): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->slug() === $slug) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Slug => label pairs, for filters that keep their state in the URL.
     *
     * @return array<string, string>
     */
    public static function slugOptions(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->slug() => $case->label()])->all();
    }
}
