<?php

namespace App\Data;

/**
 * Fine-tuning knobs, each on a 0–100 scale, that shape which tracks get picked.
 */
final readonly class Tuning
{
    public function __construct(
        public int $energy = 50,
        public int $popularity = 50,
        public int $instrumentalness = 20,
    ) {}

    /**
     * Build a tuning from untrusted values, clamped to 0–100 and snapped to the sliders' 5-point steps.
     *
     * @param  array{energy?: mixed, popularity?: mixed, instrumentalness?: mixed}  $values
     */
    public static function fromArray(array $values): self
    {
        $defaults = new self;
        $knob = fn (string $key, int $default): int => is_numeric($values[$key] ?? null)
            ? (int) (round(max(0, min(100, (float) $values[$key])) / 5) * 5)
            : $default;

        return new self(
            energy: $knob('energy', $defaults->energy),
            popularity: $knob('popularity', $defaults->popularity),
            instrumentalness: $knob('instrumentalness', $defaults->instrumentalness),
        );
    }

    /**
     * Describe the tuning in words an LLM can follow.
     *
     * @return list<string>
     */
    public function describe(): array
    {
        return [
            'Energy: '.self::band($this->energy, [
                'very calm and soft', 'mellow and laid-back', 'moderate energy', 'energetic', 'very high energy, intense',
            ]),
            'Popularity: '.self::band($this->popularity, [
                'obscure deep cuts only', 'mostly lesser-known gems', 'a mix of known and lesser-known songs', 'mostly well-known songs', 'huge mainstream hits only',
            ]),
            'Vocals: '.self::band($this->instrumentalness, [
                'vocal-driven songs only', 'mostly songs with vocals', 'a mix of vocal and instrumental tracks', 'mostly instrumental tracks', 'fully instrumental tracks only',
            ]),
        ];
    }

    /**
     * @param  array{0: string, 1: string, 2: string, 3: string, 4: string}  $labels
     */
    private static function band(int $value, array $labels): string
    {
        $index = min(4, intdiv(max(0, min(100, $value)), 20));

        return $labels[$index];
    }
}
