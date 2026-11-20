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
    public function __construct(private readonly OpenAiClient $openAi) {}

    public function interpret(string $phrase): Interpretation
    {
        $options = fn (array $values): string => implode(', ', $values);

        $result = $this->openAi->json(
            instructions: 'You are a music curator. You read a short description of a situation, moment or feeling, '
                .'written in any language, and describe the music it calls for. Always answer labels in English with short Title Case words. '
                .'Prefer one of the suggested options; use a different label only when none fits.',
            prompt: implode("\n", [
                "Situation: \"{$phrase}\"",
                '',
                'Pick:',
                "- dayTime: the time of day it evokes (suggested: {$options(MoodCategories::DAY_TIMES)})",
                "- mood: the emotion (suggested: {$options(MoodCategories::MOODS)})",
                "- relationship: who the listener is with (suggested: {$options(MoodCategories::RELATIONSHIPS)})",
                "- genre: one specific musical genre, never a feeling (suggested: {$options(MoodCategories::GENRES)})",
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
                'required' => ['dayTime', 'mood', 'relationship', 'genre', 'energy', 'popularity', 'instrumentalness'],
                'properties' => [
                    'dayTime' => ['type' => 'string'],
                    'mood' => ['type' => 'string'],
                    'relationship' => ['type' => 'string'],
                    'genre' => ['type' => 'string'],
                    'energy' => ['type' => 'integer'],
                    'popularity' => ['type' => 'integer'],
                    'instrumentalness' => ['type' => 'integer'],
                ],
            ],
        );

        return new Interpretation(MoodProfile::fromArray($result), Tuning::fromArray($result));
    }
}
