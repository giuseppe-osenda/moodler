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
     * Normalize a YouTube video or channel title. These put the artist and the song on either
     * side of a dash ("Al Green - Let's Stay Together"), so every part is kept, and they come
     * HTML-escaped ("Let&#39;s").
     */
    public static function normalizeVideoTitle(string $value): string
    {
        $parts = preg_split('/\s+[-–—|]\s+/u', html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?: [];

        return collect($parts)->map(self::normalize(...))->filter()->implode(' ');
    }

    /**
     * Whether a normalized candidate name and the wanted one contain each other.
     */
    public static function overlap(string $candidate, string $wanted): bool
    {
        return $candidate !== '' && (str_contains($candidate, $wanted) || str_contains($wanted, $candidate));
    }
}
