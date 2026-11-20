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
            '- '.$this->describeMoods($profile->moods),
            "- Listening with: {$profile->relationship}",
            '- '.$this->describeGenres($profile->genres),
            ...array_map(fn (string $line): string => "- {$line}", $tuning->describe()),
            '',
            ...$this->blendingRules($profile),
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

    /**
     * @param  list<string>  $moods
     */
    private function describeMoods(array $moods): string
    {
        return count($moods) === 1
            ? "Mood: {$moods[0]}"
            : 'Mood: one emotional tone that blends '.implode(' + ', $moods).', felt in every song';
    }

    /**
     * @param  list<string>  $genres
     */
    private function describeGenres(array $genres): string
    {
        return count($genres) === 1
            ? "Genre: {$genres[0]}"
            : 'Genres: '.implode(', ', $genres).'. Every song clearly belongs to at least one of them';
    }

    /**
     * Extra guidance that keeps a list coherent when it mixes several moods or genres.
     *
     * @return list<string>
     */
    private function blendingRules(MoodProfile $profile): array
    {
        $rules = [];

        if (count($profile->moods) > 1) {
            $rules[] = 'When the moods pull in different directions, resolve them the way the moment described above suggests.';
        }

        if (count($profile->genres) > 1) {
            $rules[] = 'Share the list roughly evenly between the genres and favor songs and artists that sit where they meet.';
            $rules[] = 'Order the list so it flows like one set: place songs that bridge two genres between songs of different genres, and avoid abrupt jumps.';
        }

        return $rules;
    }
}
