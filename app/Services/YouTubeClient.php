<?php

namespace App\Services;

use App\Exceptions\YouTubeUnavailable;
use App\Support\SongNames;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Finds playable YouTube videos for a song with the YouTube Data API.
 *
 * Searches are scarce (a project gets 100 search.list calls a day), so every answer is
 * cached: the candidate videos for 30 days, the longest YouTube's developer policies allow
 * keeping API data, and misses for a week. Several candidates are kept,
 * best first, so a video that turns out not to play can be swapped for the next one
 * without searching again. Once the quota runs out, no more searches are sent until it
 * resets at midnight Pacific Time.
 */
class YouTubeClient
{
    private const VIDEO_CACHE_PREFIX = 'youtube-video:';

    private const QUOTA_EXHAUSTED_KEY = 'youtube-quota-exhausted';

    private const VIDEO_CACHE_DAYS = 30;

    private const MISS_CACHE_DAYS = 7;

    private const MUSIC_CATEGORY_ID = '10';

    private const MAX_RESULTS = 8;

    private const MAX_CANDIDATES = 5;

    /**
     * Words that point to a different recording than the one wanted, unless the song title has them too.
     */
    private const OTHER_VERSION_WORDS = ['live', 'cover', 'karaoke', 'remix', 'reaction', 'instrumental', 'slowed', 'sped up', '8d', 'lesson', 'tutorial'];

    /**
     * Words of videos that play the right recording, but over lyrics or a fan-made picture: kept as a fallback.
     */
    private const FALLBACK_WORDS = ['lyrics', 'lyric', 'testo', 'letra', 'paroles', 'sub', 'traduzione'];

    public function __construct(
        private readonly ?string $apiKey,
        private readonly string $apiUrl = 'https://www.googleapis.com/youtube/v3',
        private readonly string $regionCode = 'IT',
    ) {}

    public function isConfigured(): bool
    {
        return filled($this->apiKey);
    }

    /**
     * Whether the answer for this song is already known, so looking it up costs no quota.
     */
    public function isCached(string $title, string $artist): bool
    {
        return $this->cachedEntry($title, $artist) !== null;
    }

    /**
     * The id of the best video of the song that is not excluded, or null when YouTube has none left.
     *
     * @param  list<string>  $exclude  videos the player could not play
     *
     * @throws YouTubeUnavailable
     */
    public function findVideo(string $title, string $artist, array $exclude = []): ?string
    {
        $candidates = $this->cachedEntry($title, $artist)['videos'] ?? null;

        if ($candidates === null) {
            $candidates = $this->search($title, $artist);
            $this->store($title, $artist, $candidates, now()->addDays($candidates === [] ? self::MISS_CACHE_DAYS : self::VIDEO_CACHE_DAYS));
        }

        return collect($candidates)->first(fn (string $videoId): bool => ! in_array($videoId, $exclude, true));
    }

    /**
     * Move a video a player could not play behind the song's other candidates, so others try those first.
     * Only reorders, and keeps the original expiry: a wrong report cannot hide a song or keep it cached longer.
     */
    public function demote(string $title, string $artist, string $videoId): void
    {
        $entry = $this->cachedEntry($title, $artist);

        if ($entry === null || ! in_array($videoId, $entry['videos'], true)) {
            return;
        }

        $this->store(
            $title,
            $artist,
            [...array_values(array_diff($entry['videos'], [$videoId])), $videoId],
            Carbon::createFromTimestamp($entry['expiresAt']),
        );
    }

    /**
     * What is cached for the song, or null when it must be looked up.
     *
     * @return array{videos: list<string>, expiresAt: int}|null
     */
    private function cachedEntry(string $title, string $artist): ?array
    {
        $cached = Cache::get($this->cacheKey($title, $artist));

        if (is_array($cached) && is_array($cached['videos'] ?? null) && is_int($cached['expiresAt'] ?? null)) {
            return ['videos' => array_values($cached['videos']), 'expiresAt' => $cached['expiresAt']];
        }

        // Entries from before the 30-day limit were kept forever: give them the limit now.
        $legacyVideos = match (true) {
            is_array($cached) && array_is_list($cached) && $cached !== [] => array_values($cached),
            is_string($cached) && $cached !== '' => [$cached],
            // Old misses ('') came from a matching bug: look those songs up again.
            default => null,
        };

        if ($legacyVideos === null) {
            return null;
        }

        $expiresAt = now()->addDays(self::VIDEO_CACHE_DAYS);
        $this->store($title, $artist, $legacyVideos, $expiresAt);

        return ['videos' => $legacyVideos, 'expiresAt' => $expiresAt->getTimestamp()];
    }

    /**
     * @param  list<string>  $videos
     */
    private function store(string $title, string $artist, array $videos, Carbon $expiresAt): void
    {
        if ($expiresAt->isPast()) {
            Cache::forget($this->cacheKey($title, $artist));

            return;
        }

        Cache::put($this->cacheKey($title, $artist), ['videos' => $videos, 'expiresAt' => $expiresAt->getTimestamp()], $expiresAt);
    }

    /**
     * @return list<string>
     *
     * @throws YouTubeUnavailable
     */
    private function search(string $title, string $artist): array
    {
        if (! $this->isConfigured()) {
            throw new YouTubeUnavailable('The YouTube API key is not configured (YOUTUBE_API_KEY).');
        }

        if (Cache::has(self::QUOTA_EXHAUSTED_KEY)) {
            throw new YouTubeUnavailable('The YouTube search quota is used up for today.');
        }

        $response = Http::timeout(10)->get(rtrim($this->apiUrl, '/').'/search', array_filter([
            'part' => 'snippet',
            'type' => 'video',
            'videoEmbeddable' => 'true',
            'videoSyndicated' => 'true',
            'videoCategoryId' => self::MUSIC_CATEGORY_ID,
            'regionCode' => $this->regionCode,
            'maxResults' => self::MAX_RESULTS,
            'q' => "{$artist} {$title}",
            'key' => $this->apiKey,
        ], filled(...)));

        if ($response->status() === 403 && collect($response->json('error.errors', []))->contains('reason', 'quotaExceeded')) {
            Cache::put(self::QUOTA_EXHAUSTED_KEY, true, $this->nextQuotaReset());

            throw new YouTubeUnavailable('The YouTube search quota is used up for today.');
        }

        if ($response->failed()) {
            Log::warning('YouTube search failed.', ['status' => $response->status(), 'reason' => $response->json('error.errors.0.reason')]);

            throw new YouTubeUnavailable("YouTube search failed with status {$response->status()}.");
        }

        return $this->candidates($response->json('items', []), $title, $artist);
    }

    /**
     * The results that are the song, most likely the original recording first.
     *
     * @param  list<array<string, mixed>>  $items
     * @return list<string>
     */
    private function candidates(array $items, string $title, string $artist): array
    {
        $wantedTitle = SongNames::normalize($title);
        $wantedArtist = SongNames::normalize($artist);

        return collect($items)
            ->filter(fn (mixed $item): bool => is_array($item) && is_string($item['id']['videoId'] ?? null))
            ->values()
            ->map(function (array $item, int $position) use ($wantedTitle, $wantedArtist): array {
                $rawTitle = html_entity_decode((string) ($item['snippet']['title'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $videoTitle = SongNames::normalizeVideoTitle($rawTitle);
                // Version words often sit in brackets, which normalizing drops: look for them in the whole title.
                $titleWords = Str::of($rawTitle)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->toString();
                $channelTitle = html_entity_decode((string) ($item['snippet']['channelTitle'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $channel = SongNames::normalizeVideoTitle($channelTitle);
                $isTopicChannel = str_ends_with($channelTitle, ' - Topic');
                $isArtistChannel = SongNames::overlap($channel, $wantedArtist);
                $hasWord = fn (string $word): bool => preg_match("/\\b{$word}\\b/", $titleWords) === 1 && ! str_contains($wantedTitle, $word);

                return [
                    'videoId' => $item['id']['videoId'],
                    'eligible' => str_contains($videoTitle, $wantedTitle)
                        && ($isArtistChannel || str_contains($videoTitle, $wantedArtist))
                        && ! collect(self::OTHER_VERSION_WORDS)->contains($hasWord),
                    'score' => ($isTopicChannel ? 2 : 0) + ($isArtistChannel ? 1 : 0) - (collect(self::FALLBACK_WORDS)->contains($hasWord) ? 2 : 0),
                    'position' => $position,
                ];
            })
            ->filter(fn (array $candidate): bool => $candidate['eligible'])
            ->sortBy([['score', 'desc'], ['position', 'asc']])
            ->take(self::MAX_CANDIDATES)
            ->pluck('videoId')
            ->values()
            ->all();
    }

    private function nextQuotaReset(): Carbon
    {
        return now('America/Los_Angeles')->addDay()->startOfDay();
    }

    private function cacheKey(string $title, string $artist): string
    {
        return self::VIDEO_CACHE_PREFIX.sha1(SongNames::normalize($artist).'|'.SongNames::normalize($title));
    }
}
