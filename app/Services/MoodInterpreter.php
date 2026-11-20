<?php

namespace App\Services;

use App\Data\Interpretation;
use App\Data\MoodProfile;
use App\Data\Tuning;
use App\Support\MoodCategories;

/**
 * Turns a free-form phrase into a mood profile and a starting point for the tuning knobs.
 */
class MoodInterpreter
{
    /**
     * Schema of a proposed mood or genre: the English value plus how to show it to the player.
     */
    private const OPTION_SCHEMA = [
        'type' => 'object',
        'additionalProperties' => false,
        'required' => ['value', 'label', 'description'],
        'properties' => [
            'value' => ['type' => 'string'],
            'label' => ['type' => 'string'],
            'description' => ['type' => 'string'],
        ],
    ];

    public function __construct(private readonly OpenAiClient $openAi) {}

    /**
     * @param  string  $locale  interface language for the labels and descriptions of proposed moods and genres
     */
    public function interpret(string $phrase, string $locale): Interpretation
    {
        $options = fn (array $values): string => implode(', ', $values);
        $language = config("app.supported_locales.{$locale}", 'English');

        $result = $this->openAi->json(
            instructions: 'You are a music curator. You read a short description of a situation, moment or feeling, '
                .'written in any language, and describe the music it calls for. Always answer values in English with short Title Case words. '
                .'Prefer one of the suggested options; use a different value only when none fits.',
            prompt: implode("\n", [
                "Situation: \"{$phrase}\"",
                '',
                'Pick:',
                "- dayTime: the time of day it evokes (suggested: {$options(MoodCategories::DAY_TIMES)})",
                "- moods: the feeling the music itself should carry, so it serves the moment. One mood, or two only when both clearly fit (suggested: {$options(MoodCategories::MOODS)})",
                '  Describe the music the moment needs, not the feeling the listener wants to leave behind: a run to blow off stress calls for Energetic music, not Relaxed.',
                '  Use Focused only for concentrating on a mental task such as work or study, never for physical activity. Moods must agree with the energy knob below.',
                "- relationship: who the listener is with (suggested: {$options(MoodCategories::RELATIONSHIPS)})",
                "- genres: one specific musical genre, or two that blend naturally; never a feeling (suggested: {$options(MoodCategories::GENRES)})",
                '',
                "For each mood and genre give its English value, a label with its name in {$language} "
                .'(always translate moods; keep the English name only for genres usually left untranslated), '
                ."and a description: one sentence in {$language}, "
                .'at most 15 words, describing how it sounds or feels.',
                '',
                'Then set three knobs from 0 to 100 so the music serves the situation:',
                '- energy: 0 = very calm and soft, 100 = very intense',
                '- popularity: 0 = obscure deep cuts, 100 = huge mainstream hits',
                '- instrumentalness: 0 = vocal-driven songs only, 100 = fully instrumental. Lyrics distract when the listener needs to focus, study or sleep.',
            ]),
            schemaName: 'mood_interpretation',
            schema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['dayTime', 'moods', 'relationship', 'genres', 'energy', 'popularity', 'instrumentalness'],
                'properties' => [
                    'dayTime' => ['type' => 'string'],
                    'moods' => ['type' => 'array', 'items' => self::OPTION_SCHEMA],
                    'relationship' => ['type' => 'string'],
                    'genres' => ['type' => 'array', 'items' => self::OPTION_SCHEMA],
                    'energy' => ['type' => 'integer'],
                    'popularity' => ['type' => 'integer'],
                    'instrumentalness' => ['type' => 'integer'],
                ],
            ],
        );

        $moods = $this->proposedOptions($result['moods'] ?? [], MoodCategories::MOODS);
        $genres = $this->proposedOptions($result['genres'] ?? [], MoodCategories::GENRES);

        $profile = MoodProfile::fromArray([
            'dayTime' => MoodCategories::builtIn(MoodCategories::DAY_TIMES, (string) ($result['dayTime'] ?? '')) ?? ($result['dayTime'] ?? ''),
            'moods' => array_column($moods, 'value'),
            'relationship' => MoodCategories::builtIn(MoodCategories::RELATIONSHIPS, (string) ($result['relationship'] ?? '')) ?? ($result['relationship'] ?? ''),
            'genres' => array_column($genres, 'value'),
        ]);

        $customOptions = collect([...$moods, ...$genres])
            ->filter(fn (array $option): bool => ! $option['builtIn'] && $option['label'] !== '')
            ->mapWithKeys(fn (array $option): array => [$option['value'] => ['label' => $option['label'], 'description' => $option['description']]])
            ->all();

        return new Interpretation($profile, Tuning::fromArray($result), $customOptions);
    }

    /**
     * Normalize the proposed options, using the built-in spelling when a value is one of the defaults.
     *
     * @param  list<string>  $defaults
     * @return list<array{value: string, label: string, description: string, builtIn: bool}>
     */
    private function proposedOptions(mixed $proposed, array $defaults): array
    {
        return collect(is_array($proposed) ? $proposed : [])
            ->filter(fn (mixed $option): bool => is_array($option) && filled($option['value'] ?? null))
            ->map(function (array $option) use ($defaults): array {
                $builtIn = MoodCategories::builtIn($defaults, (string) $option['value']);

                return [
                    'value' => $builtIn ?? mb_substr(trim((string) $option['value']), 0, 40),
                    'label' => mb_substr(trim((string) ($option['label'] ?? '')), 0, 40),
                    'description' => trim((string) ($option['description'] ?? '')),
                    'builtIn' => $builtIn !== null,
                ];
            })
            ->values()
            ->all();
    }
}
