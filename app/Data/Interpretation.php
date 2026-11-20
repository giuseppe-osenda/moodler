<?php

namespace App\Data;

/**
 * What the AI read in the player's phrase: the atmosphere and a starting point for the knobs.
 */
final readonly class Interpretation
{
    public function __construct(
        public MoodProfile $profile,
        public Tuning $tuning,
    ) {}
}
