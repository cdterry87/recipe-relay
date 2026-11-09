<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Fixed recipe collections. Creators tick them in the editor; some are suggested by a rule.
 */
enum Collection: string
{
    use HasOptions;

    case QuickAndEasy = 'Quick & Easy';
    case SlowCook = 'Slow Cook';
    case OnePot = 'One-Pot';
    case KidFriendly = 'Kid-Friendly';
    case MakeAhead = 'Make-Ahead';
    case BudgetFriendly = 'Budget-Friendly';
    case CrowdPleaser = 'Crowd-Pleaser';

    /**
     * The URL-friendly name, such as "quick-and-easy".
     */
    public function slug(): string
    {
        return str($this->value)->replace('&', 'and')->slug()->value();
    }

    /**
     * Whether a recipe with these details fits the collection's rule. Collections without a
     * rule are only ever ticked by hand.
     */
    public function suggests(?int $totalMinutes, ?Difficulty $difficulty, ?Method $method): bool
    {
        return match ($this) {
            self::QuickAndEasy => $totalMinutes !== null && $totalMinutes > 0 && $totalMinutes <= 30 && $difficulty === Difficulty::Easy,
            self::SlowCook => in_array($method, [Method::SlowCooker, Method::Smoked], true) || ($totalMinutes ?? 0) >= 240,
            default => false,
        };
    }
}
