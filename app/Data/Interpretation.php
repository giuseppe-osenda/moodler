<?php

namespace App\Data;

/**
 * What the AI read in the player's phrase: the atmosphere, a starting point for the knobs, and the
 * interface-language label and description of any proposed mood or genre the app does not know.
 */
final readonly class Interpretation
{
    /**
     * @param  array<string, array{label: string, description: string}>  $customOptions  keyed by value
     */
    public function __construct(
        public MoodProfile $profile,
        public Tuning $tuning,
        public array $customOptions = [],
    ) {}
}
