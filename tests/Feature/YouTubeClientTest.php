<?php

namespace Tests\Feature;

use App\Exceptions\YouTubeUnavailable;
use App\Services\YouTubeClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class YouTubeClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config(['services.youtube.key' => 'yt-key']);
    }

    /**
     * @return array{id: array{videoId: string}, snippet: array{title: string, channelTitle: string}}
     */
    private function video(string $id, string $title, string $channel): array
    {
        return ['id' => ['kind' => 'youtube#video', 'videoId' => $id], 'snippet' => ['title' => $title, 'channelTitle' => $channel]];
    }

    public function test_it_prefers_the_official_audio_over_covers_and_live_versions(): void
    {
        Http::fake(['www.googleapis.com/youtube/v3/search*' => Http::response(['items' => [
            $this->video('cover111111', 'Dreams - Fleetwood Mac (acoustic cover)', 'Some Busker'),
            $this->video('live2222222', 'Fleetwood Mac - Dreams (Live 1997)', 'Fleetwood Mac'),
            $this->video('video333333', 'Fleetwood Mac - Dreams (Official Music Video)', 'Fleetwood Mac'),
            $this->video('topic444444', 'Dreams (2004 Remaster)', 'Fleetwood Mac - Topic'),
        ]])]);

        $this->assertSame('topic444444', app(YouTubeClient::class)->findVideo('Dreams', 'Fleetwood Mac'));

        Http::assertSent(fn (Request $request): bool => $request['type'] === 'video'
            && $request['videoEmbeddable'] === 'true'
            && $request['videoSyndicated'] === 'true'
            && $request['regionCode'] === 'IT'
            && $request['videoCategoryId'] === '10'
            && $request['q'] === 'Fleetwood Mac Dreams'
            && $request['key'] === 'yt-key');
    }

    public function test_it_recognizes_official_videos_titled_artist_dash_song_with_escaped_characters(): void
    {
        Http::fake(['www.googleapis.com/youtube/v3/search*' => Http::response(['items' => [
            $this->video('lyrics11111', 'Al Green - Let&#39;s Stay Together (Lyrics)', 'Aiden Music'),
            $this->video('official222', 'Al Green - Let&#39;s Stay Together (Official Audio)', 'Al Green'),
            $this->video('jools333333', 'Al Green - Let&#39;s Stay Together | Later.. with Jools Holland (1993)', 'BBC Music'),
        ]])]);

        $this->assertSame('official222', app(YouTubeClient::class)->findVideo("Let's Stay Together", 'Al Green'));
    }

    public function test_when_a_video_does_not_play_the_next_candidate_is_used_without_searching_again(): void
    {
        Http::fake(['www.googleapis.com/youtube/v3/search*' => Http::response(['items' => [
            $this->video('topic444444', 'Dreams', 'Fleetwood Mac - Topic'),
            $this->video('video333333', 'Fleetwood Mac - Dreams (Official Music Video)', 'Fleetwood Mac'),
            $this->video('cover111111', 'Dreams - Fleetwood Mac (acoustic cover)', 'Some Busker'),
        ]])]);

        $youtube = app(YouTubeClient::class);

        $this->assertSame('topic444444', $youtube->findVideo('Dreams', 'Fleetwood Mac'));
        $this->assertSame('video333333', $youtube->findVideo('Dreams', 'Fleetwood Mac', ['topic444444']));
        $this->assertNull($youtube->findVideo('Dreams', 'Fleetwood Mac', ['topic444444', 'video333333']));
        Http::assertSentCount(1);
    }

    public function test_a_reported_video_moves_behind_the_others_but_is_never_dropped(): void
    {
        Cache::forever('youtube-video:'.sha1('fleetwood mac|dreams'), ['topic444444', 'video333333']);
        $youtube = app(YouTubeClient::class);

        $youtube->demote('Dreams', 'Fleetwood Mac', 'topic444444');
        $youtube->demote('Dreams', 'Fleetwood Mac', 'notacandidate');

        $this->assertSame('video333333', $youtube->findVideo('Dreams', 'Fleetwood Mac'));
        $this->assertSame('topic444444', $youtube->findVideo('Dreams', 'Fleetwood Mac', ['video333333']));
    }

    public function test_songs_cached_before_keeping_several_candidates_still_work_and_old_misses_are_searched_again(): void
    {
        Cache::forever('youtube-video:'.sha1('fleetwood mac|dreams'), 'topic444444');
        Cache::put('youtube-video:'.sha1('al green|let s stay together'), '', now()->addWeek());
        Http::fake(['www.googleapis.com/youtube/v3/search*' => Http::response(['items' => [
            $this->video('official222', 'Al Green - Let&#39;s Stay Together (Official Audio)', 'Al Green'),
        ]])]);

        $youtube = app(YouTubeClient::class);

        $this->assertSame('topic444444', $youtube->findVideo('Dreams', 'Fleetwood Mac'));
        $this->assertFalse($youtube->isCached("Let's Stay Together", 'Al Green'));
        $this->assertSame('official222', $youtube->findVideo("Let's Stay Together", 'Al Green'));
        Http::assertSentCount(1);
    }

    public function test_found_videos_are_kept_for_30_days_as_youtube_policies_allow(): void
    {
        Http::fake(['www.googleapis.com/youtube/v3/search*' => Http::response(['items' => [
            $this->video('topic444444', 'Dreams', 'Fleetwood Mac - Topic'),
            $this->video('video333333', 'Fleetwood Mac - Dreams (Official Music Video)', 'Fleetwood Mac'),
        ]])]);
        $youtube = app(YouTubeClient::class);

        $youtube->findVideo('Dreams', 'Fleetwood Mac');

        $this->travel(29)->days();
        $youtube->demote('Dreams', 'Fleetwood Mac', 'topic444444');
        $this->assertTrue($youtube->isCached('Dreams', 'Fleetwood Mac'));

        // Reordering does not restart the 30 days.
        $this->travel(2)->days();
        $this->assertFalse($youtube->isCached('Dreams', 'Fleetwood Mac'));
        $this->assertSame('topic444444', $youtube->findVideo('Dreams', 'Fleetwood Mac'));
        Http::assertSentCount(2);
    }

    public function test_videos_cached_forever_before_the_limit_get_30_days_from_now(): void
    {
        Cache::forever('youtube-video:'.sha1('fleetwood mac|dreams'), 'topic444444');
        $youtube = app(YouTubeClient::class);

        $this->assertTrue($youtube->isCached('Dreams', 'Fleetwood Mac'));

        $this->travel(31)->days();
        $this->assertFalse($youtube->isCached('Dreams', 'Fleetwood Mac'));
    }

    public function test_it_returns_null_when_no_result_is_the_song(): void
    {
        Http::fake(['www.googleapis.com/youtube/v3/search*' => Http::response(['items' => [
            $this->video('other555555', 'Fleetwood Mac - Landslide', 'Fleetwood Mac'),
        ]])]);

        $this->assertNull(app(YouTubeClient::class)->findVideo('Dreams', 'Fleetwood Mac'));
    }

    public function test_answers_are_cached_so_repeated_lookups_cost_no_quota(): void
    {
        Http::fake(['www.googleapis.com/youtube/v3/search*' => Http::sequence()
            ->push(['items' => [$this->video('topic444444', 'Dreams', 'Fleetwood Mac - Topic')]])
            ->push(['items' => []])]);

        $youtube = app(YouTubeClient::class);

        $this->assertFalse($youtube->isCached('Dreams', 'Fleetwood Mac'));
        $this->assertSame('topic444444', $youtube->findVideo('Dreams', 'Fleetwood Mac'));
        $this->assertSame('topic444444', $youtube->findVideo('Dreams (Remastered)', 'fleetwood mac'));
        $this->assertNull($youtube->findVideo('Unknown Song', 'Nobody'));
        $this->assertNull($youtube->findVideo('Unknown Song', 'Nobody'));
        $this->assertTrue($youtube->isCached('Unknown Song', 'Nobody'));

        Http::assertSentCount(2);
    }

    public function test_once_the_quota_is_used_up_it_stops_searching_until_the_reset(): void
    {
        Http::fake(['www.googleapis.com/youtube/v3/search*' => Http::response(
            ['error' => ['code' => 403, 'errors' => [['reason' => 'quotaExceeded']]]],
            403,
        )]);

        $youtube = app(YouTubeClient::class);

        foreach (['Dreams', 'Harvest Moon'] as $title) {
            try {
                $youtube->findVideo($title, 'Someone');
                $this->fail('Expected YouTube to be unavailable.');
            } catch (YouTubeUnavailable) {
                // expected
            }
        }

        Http::assertSentCount(1);
    }

    public function test_it_is_unavailable_without_an_api_key(): void
    {
        config(['services.youtube.key' => null]);

        $this->assertFalse(app(YouTubeClient::class)->isConfigured());
        $this->expectException(YouTubeUnavailable::class);

        app(YouTubeClient::class)->findVideo('Dreams', 'Fleetwood Mac');
    }
}
