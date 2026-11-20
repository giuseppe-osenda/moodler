<?php

namespace App\Data;

/**
 * The atmosphere a playlist should evoke, as interpreted from the player's phrase.
 *
 * Moods and genres can be combined, but only a few at a time: past three the
 * blend gets too thin for fifteen songs to feel like one list.
 */
final readonly class MoodProfile
{
    public const MAX_MOODS = 3;

    public const MAX_GENRES = 3;

    /**
     * @param  list<string>  $moods
     * @param  list<string>  $genres
     */
    public function __construct(
        public string $dayTime,
        public array $moods,
        public string $relationship,
        public array $genres,
    ) {}

    /**
     * @param  array{dayTime?: mixed, moods?: mixed, relationship?: mixed, genres?: mixed}  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            dayTime: trim((string) ($attributes['dayTime'] ?? '')),
            moods: self::labels($attributes['moods'] ?? [], self::MAX_MOODS),
            relationship: trim((string) ($attributes['relationship'] ?? '')),
            genres: self::labels($attributes['genres'] ?? [], self::MAX_GENRES),
        );
    }

    /**
     * Trim the labels, drop empty and repeated ones (ignoring case) and keep at most $limit.
     *
     * @return list<string>
     */
    public static function labels(mixed $values, int $limit): array
    {
        $labels = [];

        foreach ((array) $values as $value) {
            $label = is_string($value) ? trim($value) : '';

            if ($label !== '' && ! in_array(mb_strtolower($label), array_map(mb_strtolower(...), $labels), true)) {
                $labels[] = $label;
            }
        }

        return array_slice($labels, 0, $limit);
    }
}
