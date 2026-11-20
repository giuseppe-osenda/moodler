<?php

namespace Tests\Feature;

use App\Data\TrackSuggestion;
use App\Services\SpotifyClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SpotifyClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config(['services.spotify.client_id' => 'client-id', 'services.spotify.client_secret' => 'client-secret']);
    }

    /**
     * @param  list<string>  $artists
     * @return array<string, mixed>
     */
    private function spotifyTrack(string $id, string $name, array $artists): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'duration_ms' => 215000,
            'artists' => array_map(fn (string $artist): array => ['name' => $artist], $artists),
            'album' => ['name' => 'Album', 'images' => [['url' => "https://img/{$id}-640", 'width' => 640], ['url' => "https://img/{$id}-300", 'width' => 300], ['url' => "https://img/{$id}-64", 'width' => 64]]],
        ];
    }

    public function test_it_resolves_suggestions_to_tracks_whose_artist_and_title_match(): void
    {
        Http::fake([
            'accounts.spotify.com/*' => Http::response(['access_token' => 'app-token', 'expires_in' => 3600]),
            'api.spotify.com/v1/search*' => function (Request $request) {
                return str_contains($request['q'], 'Dreams')
                    ? Http::response(['tracks' => ['items' => [
                        $this->spotifyTrack('cover', 'Dreams', ['Some Cover Band']),
                        $this->spotifyTrack('dreams', 'Dreams - 2004 Remaster', ['Fleetwood Mac']),
                    ]]])
                    : Http::response(['tracks' => ['items' => []]]);
            },
        ]);

        $tracks = app(SpotifyClient::class)->resolve([
            new TrackSuggestion('Dreams', 'Fleetwood Mac'),
            new TrackSuggestion('Imaginary Song', 'Nobody'),
        ]);

        $this->assertCount(1, $tracks);
        $this->assertSame('dreams', $tracks[0]->id);
        $this->assertSame('spotify:track:dreams', $tracks[0]->uri());
        $this->assertSame('https://img/dreams-300', $tracks[0]->imageUrl);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'api.spotify.com')
            && $request->hasHeader('Authorization', 'Bearer app-token')
            && $request['q'] === 'track:"Dreams" artist:"Fleetwood Mac"');
    }

    public function test_it_retries_unmatched_suggestions_with_a_loose_query(): void
    {
        Http::fake([
            'accounts.spotify.com/*' => Http::response(['access_token' => 'app-token', 'expires_in' => 3600]),
            'api.spotify.com/v1/search*' => fn (Request $request) => str_starts_with($request['q'], 'track:')
                ? Http::response(['tracks' => ['items' => []]])
                : Http::response(['tracks' => ['items' => [$this->spotifyTrack('sg', 'The Sound of Silence', ['Simon & Garfunkel'])]]]),
        ]);

        $tracks = app(SpotifyClient::class)->resolve([new TrackSuggestion('The Sound of Silence', 'Simon and Garfunkel')]);

        $this->assertSame(['sg'], array_map(fn ($track) => $track->id, $tracks));
        Http::assertSent(fn (Request $request): bool => ($request['q'] ?? null) === 'The Sound of Silence Simon and Garfunkel');
    }

    public function test_it_drops_duplicate_tracks_and_reuses_the_cached_token(): void
    {
        Http::fake([
            'accounts.spotify.com/*' => Http::response(['access_token' => 'app-token', 'expires_in' => 3600]),
            'api.spotify.com/v1/search*' => Http::response(['tracks' => ['items' => [$this->spotifyTrack('dreams', 'Dreams', ['Fleetwood Mac'])]]]),
        ]);

        $client = app(SpotifyClient::class);
        $tracks = $client->resolve([new TrackSuggestion('Dreams', 'Fleetwood Mac'), new TrackSuggestion('Dreams', 'Fleetwood Mac')]);
        $client->resolve([new TrackSuggestion('Dreams', 'Fleetwood Mac')]);

        $this->assertCount(1, $tracks);
        Http::assertSentCount(4);
        $this->assertCount(1, Http::recorded(fn (Request $request): bool => str_contains($request->url(), 'accounts.spotify.com')));
    }
}
