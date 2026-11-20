<?php

namespace App\Services;

use App\Data\MoodProfile;
use App\Data\Track;
use App\Data\Tuning;

/**
 * Builds a playable track list: the LLM suggests songs, Spotify Search turns them into tracks.
 */
class PlaylistComposer
{
    /**
     * Ask for more suggestions than needed, since some will not be found on Spotify.
     */
    private const OVERFETCH = 6;

    public function __construct(
        private readonly TrackSuggester $suggester,
        private readonly SpotifyClient $spotify,
    ) {}

    /**
     * @param  list<string>  $avoid  "Title - Artist" strings from previous rounds
     * @return list<Track>
     */
    public function compose(string $phrase, MoodProfile $profile, Tuning $tuning, int $size = 15, array $avoid = []): array
    {
        $suggestions = $this->suggester->suggest($phrase, $profile, $tuning, $size + self::OVERFETCH, $avoid);

        return array_slice($this->spotify->resolve($suggestions), 0, $size);
    }
}
