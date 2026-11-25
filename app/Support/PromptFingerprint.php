<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Identifies a prompt regardless of letter case, spacing or the order moods and genres were picked in,
 * so asking for the same thing twice is recognized as the same request.
 */
class PromptFingerprint
{
    /**
     * @param  array{phrase: string, dayTime: string, moods: list<string>, relationship: string, genres: list<string>, energy: int, popularity: int, instrumentalness: int}  $prompt
     */
    public static function of(array $prompt): string
    {
        return hash('sha256', json_encode([
            self::normalize($prompt['phrase']),
            self::normalize($prompt['dayTime']),
            self::sorted($prompt['moods']),
            self::normalize($prompt['relationship']),
            self::sorted($prompt['genres']),
            (int) $prompt['energy'],
            (int) $prompt['popularity'],
            (int) $prompt['instrumentalness'],
        ], JSON_THROW_ON_ERROR));
    }

    private static function normalize(string $value): string
    {
        return Str::of($value)->lower()->squish()->rtrim('.!?…')->toString();
    }

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private static function sorted(array $values): array
    {
        $normalized = array_map(self::normalize(...), $values);
        sort($normalized);

        return $normalized;
    }
}
