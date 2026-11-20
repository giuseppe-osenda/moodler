<?php

namespace App\Services;

use App\Data\Track;
use App\Data\TrackSuggestion;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Spotify Web API client using the Client Credentials flow, so no Spotify login is needed from players.
 */
class SpotifyClient
{
    private const TOKEN_CACHE_PREFIX = 'spotify.app_access_token.';

    public function __construct(
        private readonly ?string $clientId,
        private readonly ?string $clientSecret,
        private readonly string $market,
        private readonly string $apiUrl = 'https://api.spotify.com/v1',
        private readonly string $accountsUrl = 'https://accounts.spotify.com',
    ) {}

    /**
     * Match each suggestion to a Spotify track, dropping the ones that cannot be found.
     *
     * @param  list<TrackSuggestion>  $suggestions
     * @return list<Track>
     */
    public function resolve(array $suggestions): array
    {
        if ($suggestions === []) {
            return [];
        }

        $matches = $this->search($suggestions, strict: true);

        $missing = array_filter($suggestions, fn (TrackSuggestion $suggestion, int $index): bool => ! isset($matches[$index]), ARRAY_FILTER_USE_BOTH);

        if ($missing !== []) {
            $matches += $this->search($missing, strict: false);
        }

        ksort($matches);

        return collect($matches)->unique(fn (Track $track): string => $track->id)->values()->all();
    }

    /**
     * Search all suggestions, renewing the access token once if Spotify rejects it.
     *
     * @param  array<int, TrackSuggestion>  $suggestions
     * @return array<int, Track>
     */
    private function search(array $suggestions, bool $strict): array
    {
        $result = $this->searchAll($this->accessToken(), $suggestions, $strict);

        if ($result['unauthorized']) {
            Cache::forget($this->tokenCacheKey());
            $result = $this->searchAll($this->accessToken(), $suggestions, $strict);
        }

        if ($result['failed'] === count($suggestions)) {
            throw new RuntimeException("Spotify search failed for every track (last status: {$result['lastStatus']}).");
        }

        if ($result['failed'] > 0) {
            Log::warning('Some Spotify searches failed.', ['failed' => $result['failed'], 'total' => count($suggestions), 'last_status' => $result['lastStatus']]);
        }

        return $result['matches'];
    }

    /**
     * Run one search per suggestion concurrently.
     *
     * @param  array<int, TrackSuggestion>  $suggestions
     * @return array{matches: array<int, Track>, failed: int, unauthorized: bool, lastStatus: int|string|null}
     */
    private function searchAll(string $token, array $suggestions, bool $strict): array
    {
        $responses = Http::pool(fn (Pool $pool): array => collect($suggestions)
            ->map(fn (TrackSuggestion $suggestion, int $index) => $pool->as((string) $index)
                ->withToken($token)
                ->acceptJson()
                ->timeout(15)
                ->get(rtrim($this->apiUrl, '/').'/search', [
                    'q' => $this->query($suggestion, $strict),
                    'type' => 'track',
                    'limit' => 5,
                    'market' => $this->market,
                ]))
            ->all());

        $matches = [];
        $failed = 0;
        $unauthorized = false;
        $lastStatus = null;

        foreach ($suggestions as $index => $suggestion) {
            $response = $responses[(string) $index] ?? null;

            if (! $response instanceof Response || $response->failed()) {
                $failed++;
                $lastStatus = $response instanceof Response ? $response->status() : 'connection error';
                $unauthorized = $unauthorized || ($response instanceof Response && $response->status() === 401);

                continue;
            }

            $match = collect($response->json('tracks.items', []))
                ->filter(fn (mixed $item): bool => is_array($item) && isset($item['id']))
                ->first(fn (array $item): bool => $this->matches($suggestion, $item));

            if ($match !== null) {
                $matches[$index] = Track::fromSpotify($match);
            }
        }

        return ['matches' => $matches, 'failed' => $failed, 'unauthorized' => $unauthorized, 'lastStatus' => $lastStatus];
    }

    private function query(TrackSuggestion $suggestion, bool $strict): string
    {
        $title = str_replace('"', '', $suggestion->title);
        $artist = str_replace('"', '', $suggestion->artist);

        return $strict ? "track:\"{$title}\" artist:\"{$artist}\"" : "{$title} {$artist}";
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function matches(TrackSuggestion $suggestion, array $item): bool
    {
        $wantedArtist = self::normalize($suggestion->artist);
        $wantedTitle = self::normalize($suggestion->title);
        $title = self::normalize((string) ($item['name'] ?? ''));

        $artistMatches = collect($item['artists'] ?? [])
            ->map(fn (array $artist): string => self::normalize((string) ($artist['name'] ?? '')))
            ->contains(fn (string $artist): bool => $artist !== '' && (str_contains($artist, $wantedArtist) || str_contains($wantedArtist, $artist)));

        $titleMatches = $title !== '' && (str_contains($title, $wantedTitle) || str_contains($wantedTitle, $title));

        return $artistMatches && $titleMatches;
    }

    private static function normalize(string $value): string
    {
        $value = Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/\(.*?\)|\[.*?\]/', ' ')
            ->replaceMatches('/\s+-\s+.*$/', ' ')
            ->replaceMatches('/\b(feat|ft|featuring)\b.*$/', ' ')
            ->replace('&', ' and ')
            ->replaceMatches('/^the\s+/', '')
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish();

        return (string) $value;
    }

    private function accessToken(): string
    {
        if (blank($this->clientId) || blank($this->clientSecret)) {
            throw new RuntimeException('Spotify credentials are not configured (SPOTIFY_CLIENT_ID / SPOTIFY_CLIENT_SECRET).');
        }

        $cached = Cache::get($this->tokenCacheKey());

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $response = Http::asForm()
            ->withBasicAuth($this->clientId, $this->clientSecret)
            ->timeout(15)
            ->post(rtrim($this->accountsUrl, '/').'/api/token', ['grant_type' => 'client_credentials']);

        $token = $response->json('access_token');

        if ($response->failed() || ! is_string($token)) {
            throw new RuntimeException('Could not authenticate with Spotify: '.$response->json('error_description', $response->status()));
        }

        Cache::put($this->tokenCacheKey(), $token, max(60, (int) $response->json('expires_in', 3600) - 60));

        return $token;
    }

    /**
     * Tokens are cached per Client ID, so changing credentials never reuses an old token.
     */
    private function tokenCacheKey(): string
    {
        return self::TOKEN_CACHE_PREFIX.sha1((string) $this->clientId);
    }
}
