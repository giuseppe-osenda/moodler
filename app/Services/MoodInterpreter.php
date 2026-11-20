<?php

namespace App\Services;

use App\Data\MoodProfile;
use App\Support\MoodCategories;

/**
 * Turns a free-form phrase into a mood profile.
 */
class MoodInterpreter
{
    public function __construct(private readonly OpenAiClient $openAi) {}

    public function interpret(string $phrase): MoodProfile
    {
        $options = fn (array $values): string => implode(', ', $values);

        $result = $this->openAi->json(
            instructions: 'You are a music curator. You read a short description of a situation, moment or feeling and '
                .'describe the music atmosphere it calls for. Always answer in English with short Title Case labels. '
                .'Prefer one of the suggested options; use a different label only when none fits.',
            prompt: implode("\n", [
                "Situation: \"{$phrase}\"",
                '',
                'Pick:',
                "- dayTime: the time of day it evokes (suggested: {$options(MoodCategories::DAY_TIMES)})",
                "- mood: the emotion (suggested: {$options(MoodCategories::MOODS)})",
                "- relationship: who the listener is with (suggested: {$options(MoodCategories::RELATIONSHIPS)})",
                "- genre: one specific musical genre, never a feeling (suggested: {$options(MoodCategories::GENRES)})",
            ]),
            schemaName: 'mood_profile',
            schema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['dayTime', 'mood', 'relationship', 'genre'],
                'properties' => [
                    'dayTime' => ['type' => 'string'],
                    'mood' => ['type' => 'string'],
                    'relationship' => ['type' => 'string'],
                    'genre' => ['type' => 'string'],
                ],
            ],
        );

        return MoodProfile::fromArray($result);
    }
}
