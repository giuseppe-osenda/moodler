<?php

namespace App\Services;

use App\Exceptions\YouTubeUnavailable;
use App\Support\SongNames;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Finds a playable YouTube video for a song with the YouTube Data API.
 *
 * Searches are scarce (a project gets 100 search.list calls a day), so every answer is
 * cached: found videos forever, misses for a week. Once the quota runs out, no more
 * searches are sent until it resets at midnight Pacific Time.
 */
class YouTubeClient
{
    private const VIDEO_CACHE_PREFIX = 'youtube-video:';

    private const QUOTA_EXHAUSTED_KEY = 'youtube-quota-exhausted';

    private const MISS_CACHE_DAYS = 7;

    private const MUSIC_CATEGORY_ID = '10';

    /**
     * Words that point to a different recording than the one wanted, unless the song title has them too.
     */
    private const OTHER_VERSION_WORDS = ['live', 'cover', 'karaoke', 'remix', 'reaction', 'instrumental', 'slowed', 'sped up', '8d', 'lesson', 'tutorial'];

    public function __construct(
        private readonly ?string $apiKey,
        private readonly string $apiUrl = 'https://www.googleapis.com/youtube/v3',
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
        return Cache::has($this->cacheKey($title, $artist));
    }

    /**
     * The id of an embeddable video of the song, or null when YouTube has none that matches.
     *
     * @throws YouTubeUnavailable
     */
    public function findVideo(string $title, string $artist): ?string
    {
        $cached = Cache::get($this->cacheKey($title, $artist));

        if (is_string($cached)) {
            return $cached === '' ? null : $cached;
        }

        $videoId = $this->search($title, $artist);

        $videoId === null
            ? Cache::put($this->cacheKey($title, $artist), '', now()->addDays(self::MISS_CACHE_DAYS))
            : Cache::forever($this->cacheKey($title, $artist), $videoId);

        return $videoId;
    }

    /**
     * @throws YouTubeUnavailable
     */
    private function search(string $title, string $artist): ?string
    {
        if (! $this->isConfigured()) {
            throw new YouTubeUnavailable('The YouTube API key is not configured (YOUTUBE_API_KEY).');
        }

        if (Cache::has(self::QUOTA_EXHAUSTED_KEY)) {
            throw new YouTubeUnavailable('The YouTube search quota is used up for today.');
        }

        $response = Http::timeout(10)->get(rtrim($this->apiUrl, '/').'/search', [
            'part' => 'snippet',
            'type' => 'video',
            'videoEmbeddable' => 'true',
            'videoCategoryId' => self::MUSIC_CATEGORY_ID,
            'maxResults' => 5,
            'q' => "{$artist} {$title}",
            'key' => $this->apiKey,
        ]);

        if ($response->status() === 403 && collect($response->json('error.errors', []))->contains('reason', 'quotaExceeded')) {
            Cache::put(self::QUOTA_EXHAUSTED_KEY, true, $this->nextQuotaReset());

            throw new YouTubeUnavailable('The YouTube search quota is used up for today.');
        }

        if ($response->failed()) {
            Log::warning('YouTube search failed.', ['status' => $response->status(), 'reason' => $response->json('error.errors.0.reason')]);

            throw new YouTubeUnavailable("YouTube search failed with status {$response->status()}.");
        }

        return $this->bestMatch($response->json('items', []), $title, $artist);
    }

    /**
     * Pick the result that is most likely the original recording of the song.
     *
     * @param  list<array<string, mixed>>  $items
     */
    private function bestMatch(array $items, string $title, string $artist): ?string
    {
        $wantedTitle = SongNames::normalize($title);
        $wantedArtist = SongNames::normalize($artist);

        return collect($items)
            ->filter(fn (mixed $item): bool => is_array($item) && is_string($item['id']['videoId'] ?? null))
            ->map(function (array $item) use ($wantedTitle, $wantedArtist): array {
                $videoTitle = SongNames::normalize((string) ($item['snippet']['title'] ?? ''));
                $channel = (string) ($item['snippet']['channelTitle'] ?? '');
                $isTopicChannel = str_ends_with($channel, ' - Topic');
                $artistMatches = SongNames::overlap(SongNames::normalize($channel), $wantedArtist) || str_contains($videoTitle, $wantedArtist);
                $isOtherVersion = collect(self::OTHER_VERSION_WORDS)
                    ->contains(fn (string $word): bool => preg_match("/\\b{$word}\\b/", $videoTitle) === 1 && ! str_contains($wantedTitle, $word));

                return [
                    'videoId' => $item['id']['videoId'],
                    'eligible' => str_contains($videoTitle, $wantedTitle) && $artistMatches && ! $isOtherVersion,
                    'score' => ($isTopicChannel ? 2 : 0) + (SongNames::overlap(SongNames::normalize($channel), $wantedArtist) ? 1 : 0),
                ];
            })
            ->filter(fn (array $candidate): bool => $candidate['eligible'])
            ->sortByDesc('score')
            ->first()['videoId'] ?? null;
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
