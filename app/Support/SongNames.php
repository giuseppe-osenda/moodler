<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Compares song titles and artist names the loose way people write them.
 */
class SongNames
{
    /**
     * Lowercase ASCII words only, without featuring credits, bracketed notes, "- Remastered" tails or a leading "the".
     */
    public static function normalize(string $value): string
    {
        return (string) Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/\(.*?\)|\[.*?\]/', ' ')
            ->replaceMatches('/\s+-\s+.*$/', ' ')
            ->replaceMatches('/\b(feat|ft|featuring)\b.*$/', ' ')
            ->replace('&', ' and ')
            ->replaceMatches('/^the\s+/', '')
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish();
    }

    /**
     * Whether a normalized candidate name and the wanted one contain each other.
     */
    public static function overlap(string $candidate, string $wanted): bool
    {
        return $candidate !== '' && (str_contains($candidate, $wanted) || str_contains($wanted, $candidate));
    }
}
