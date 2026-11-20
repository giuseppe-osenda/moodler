<?php

namespace Tests\Feature;

use App\Data\TrackSuggestion;
use App\Services\SpotifyClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
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

    public function test_it_renews_a_rejected_token_and_retries(): void
    {
        Http::fake([
            'accounts.spotify.com/*' => Http::sequence()
                ->push(['access_token' => 'expired-token', 'expires_in' => 3600])
                ->push(['access_token' => 'fresh-token', 'expires_in' => 3600]),
            'api.spotify.com/v1/search*' => fn (Request $request) => $request->hasHeader('Authorization', 'Bearer fresh-token')
                ? Http::response(['tracks' => ['items' => [$this->spotifyTrack('dreams', 'Dreams', ['Fleetwood Mac'])]]])
                : Http::response(['error' => ['status' => 401, 'message' => 'Invalid access token']], 401),
        ]);

        $client = app(SpotifyClient::class);

        $this->assertSame(['dreams'], array_map(fn ($track) => $track->id, $client->resolve([new TrackSuggestion('Dreams', 'Fleetwood Mac')])));
        $this->assertSame(['dreams'], array_map(fn ($track) => $track->id, $client->resolve([new TrackSuggestion('Dreams', 'Fleetwood Mac')])));
        $this->assertCount(2, Http::recorded(fn (Request $request): bool => str_contains($request->url(), 'accounts.spotify.com')));
    }

    public function test_tokens_cached_for_other_credentials_are_not_reused(): void
    {
        Http::fake([
            'accounts.spotify.com/*' => fn (Request $request) => Http::response([
                'access_token' => $request->hasHeader('Authorization', 'Basic '.base64_encode('client-id:client-secret')) ? 'real-token' : 'other-token',
                'expires_in' => 3600,
            ]),
            'api.spotify.com/v1/search*' => Http::response(['tracks' => ['items' => [$this->spotifyTrack('dreams', 'Dreams', ['Fleetwood Mac'])]]]),
        ]);

        (new SpotifyClient('other-id', 'other-secret', 'IT'))->resolve([new TrackSuggestion('Dreams', 'Fleetwood Mac')]);
        app(SpotifyClient::class)->resolve([new TrackSuggestion('Dreams', 'Fleetwood Mac')]);

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'api.spotify.com') && $request->hasHeader('Authorization', 'Bearer real-token'));
    }

    public function test_it_fails_loudly_when_every_search_is_rejected(): void
    {
        Http::fake([
            'accounts.spotify.com/*' => Http::response(['access_token' => 'app-token', 'expires_in' => 3600]),
            'api.spotify.com/v1/search*' => Http::response(['error' => ['status' => 403, 'message' => 'Forbidden']], 403),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('403');

        app(SpotifyClient::class)->resolve([new TrackSuggestion('Dreams', 'Fleetwood Mac')]);
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
