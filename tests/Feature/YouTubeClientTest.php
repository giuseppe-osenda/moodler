<?php

namespace Tests\Feature;

use App\Exceptions\YouTubeUnavailable;
use App\Services\YouTubeClient;
use Illuminate\Http\Client\Request;
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
            && $request['videoCategoryId'] === '10'
            && $request['q'] === 'Fleetwood Mac Dreams'
            && $request['key'] === 'yt-key');
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
