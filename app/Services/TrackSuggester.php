<?php

namespace App\Services;

use App\Data\MoodProfile;
use App\Data\TrackSuggestion;
use App\Data\Tuning;

/**
 * Asks the LLM for songs that fit a mood profile and tuning.
 */
class TrackSuggester
{
    public function __construct(private readonly OpenAiClient $openAi) {}

    /**
     * @param  list<string>  $avoid  "Title - Artist" strings that should not be suggested again
     * @return list<TrackSuggestion>
     */
    public function suggest(string $phrase, MoodProfile $profile, Tuning $tuning, int $count, array $avoid = []): array
    {
        $lines = [
            "The listener described their moment as: \"{$phrase}\"",
            '',
            "Suggest {$count} real, existing songs available on Spotify. Every song must fit all of these:",
            "- Time of day: {$profile->dayTime}",
            "- Mood: {$profile->mood}",
            "- Listening with: {$profile->relationship}",
            "- Genre: {$profile->genre}",
            ...array_map(fn (string $line): string => "- {$line}", $tuning->describe()),
            '',
            'Use at most two songs per artist. Use the exact official song title and main artist name.',
        ];

        if ($avoid !== []) {
            $lines[] = '';
            $lines[] = 'Do not suggest any of these songs, the listener already heard them:';

            foreach ($avoid as $song) {
                $lines[] = "- {$song}";
            }
        }

        $result = $this->openAi->json(
            instructions: 'You are a music curator with encyclopedic knowledge of recorded music. You only suggest songs that really exist.',
            prompt: implode("\n", $lines),
            schemaName: 'track_suggestions',
            schema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['tracks'],
                'properties' => [
                    'tracks' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'required' => ['title', 'artist'],
                            'properties' => [
                                'title' => ['type' => 'string'],
                                'artist' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
            ],
            reasoningEffort: 'low',
        );

        return collect($result['tracks'] ?? [])
            ->filter(fn (mixed $track): bool => is_array($track) && filled($track['title'] ?? null) && filled($track['artist'] ?? null))
            ->map(fn (array $track): TrackSuggestion => new TrackSuggestion(trim($track['title']), trim($track['artist'])))
            ->values()
            ->all();
    }
}
