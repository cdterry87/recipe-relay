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

    public function description(): string
    {
        return match ($this) {
            self::QuickAndEasy => 'Easy recipes on the table in 30 minutes or less.',
            self::SlowCook => 'Low and slow: set it going and come back to something wonderful.',
            self::OnePot => 'One pot or pan, and far fewer dishes.',
            self::KidFriendly => 'Food that small people actually eat.',
            self::MakeAhead => 'Cook today, enjoy tomorrow. Great for busy weeks.',
            self::BudgetFriendly => 'Big flavour from everyday ingredients.',
            self::CrowdPleaser => 'For parties, potlucks and feeding a houseful.',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::QuickAndEasy => '⏱️',
            self::SlowCook => '🍲',
            self::OnePot => '🥘',
            self::KidFriendly => '🧒',
            self::MakeAhead => '📦',
            self::BudgetFriendly => '🪙',
            self::CrowdPleaser => '🎉',
        };
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
