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
        'Amused', 'Angry', 'Bored', 'Confident', 'Cozy', 'Determined', 'Energetic', 'Excited', 'Focused', 'Grateful', 'Hopeful',
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
     * Return the default options plus any of the given values that are not already among them.
     *
     * @param  list<string>  $defaults
     * @param  string|list<string>|null  $values
     * @return list<string>
     */
    public static function withValues(array $defaults, string|array|null $values): array
    {
        $options = $defaults;

        foreach ((array) $values as $value) {
            $value = trim((string) $value);

            if ($value === '') {
                continue;
            }

            foreach ($options as $option) {
                if (strcasecmp($option, $value) === 0) {
                    continue 2;
                }
            }

            $options[] = $value;
        }

        return $options;
    }

    /**
     * Return the built-in spelling of a value when it is one of the defaults (ignoring case), or null.
     *
     * @param  list<string>  $defaults
     */
    public static function builtIn(array $defaults, string $value): ?string
    {
        foreach ($defaults as $option) {
            if (strcasecmp($option, trim($value)) === 0) {
                return $option;
            }
        }

        return null;
    }
}
