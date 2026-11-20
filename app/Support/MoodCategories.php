<?php

namespace App\Support;

/**
 * Default options the player can pick from for each mood dimension.
 */
class MoodCategories
{
    /** @var list<string> */
    public const DAY_TIMES = ['Dawn', 'Morning', 'Afternoon', 'Dusk', 'Evening', 'Night'];

    /** @var list<string> */
    public const MOODS = [
        'Amused', 'Angry', 'Bored', 'Confident', 'Cozy', 'Excited', 'Grateful', 'Hopeful',
        'Inspirational', 'Joy', 'Loving', 'Melancholic', 'Nostalgic', 'Relaxed', 'Sad', 'Tired',
    ];

    /** @var list<string> */
    public const RELATIONSHIPS = ['Alone', 'Colleagues', 'Family', 'Friends', 'Lovers'];

    /** @var list<string> */
    public const GENRES = [
        'Alternative', 'Blues', 'Classical', 'Country', 'Disco', 'Dubstep', 'EDM', 'Folk', 'Funk',
        'Gospel', 'Hip-Hop', 'Indie', 'Jazz', 'Latin', 'Lo-Fi', 'Metal', 'Pop', 'Punk', 'R&B',
        'Reggae', 'Reggaeton', 'Rock', 'Soul', 'Techno',
    ];

    /**
     * Return the default options plus the given value when it is not already one of them.
     *
     * @param  list<string>  $defaults
     * @return list<string>
     */
    public static function withValue(array $defaults, ?string $value): array
    {
        $value = trim((string) $value);

        if ($value === '') {
            return $defaults;
        }

        foreach ($defaults as $option) {
            if (strcasecmp($option, $value) === 0) {
                return $defaults;
            }
        }

        return [...$defaults, $value];
    }
}
