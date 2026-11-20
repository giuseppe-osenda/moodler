<?php

namespace App\Data;

/**
 * The atmosphere a playlist should evoke, as interpreted from the player's phrase.
 */
final readonly class MoodProfile
{
    public function __construct(
        public string $dayTime,
        public string $mood,
        public string $relationship,
        public string $genre,
    ) {}

    /**
     * @param  array{dayTime?: mixed, mood?: mixed, relationship?: mixed, genre?: mixed}  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            dayTime: trim((string) ($attributes['dayTime'] ?? '')),
            mood: trim((string) ($attributes['mood'] ?? '')),
            relationship: trim((string) ($attributes['relationship'] ?? '')),
            genre: trim((string) ($attributes['genre'] ?? '')),
        );
    }
}
