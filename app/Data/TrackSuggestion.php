<?php

namespace App\Data;

/**
 * A song proposed by the LLM, not yet matched to a Spotify track.
 */
final readonly class TrackSuggestion
{
    public function __construct(
        public string $title,
        public string $artist,
    ) {}
}
