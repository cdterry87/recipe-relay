<?php

namespace App\Enums;

/**
 * The emails a member can turn on or off. Account emails (verification, password reset)
 * are always sent and are not listed here.
 */
enum EmailPreference: string
{
    case Digest = 'digest';
    case NewFollower = 'new-follower';
    case NewRating = 'new-rating';

    public function default(): bool
    {
        return $this === self::Digest;
    }

    public function label(): string
    {
        return match ($this) {
            self::Digest => 'Weekly digest',
            self::NewFollower => 'New followers',
            self::NewRating => 'New ratings',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Digest => 'A once-a-week round-up of new recipes from cooks you follow, plus what is trending.',
            self::NewFollower => 'An email when someone starts following you.',
            self::NewRating => 'An email the first time someone rates one of your recipes.',
        };
    }
}
