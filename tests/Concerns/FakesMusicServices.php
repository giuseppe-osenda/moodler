<?php

namespace Tests\Concerns;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * Stands in for OpenAI and Spotify, so composing a list works without the network.
 */
trait FakesMusicServices
{
    protected function configureMusicServices(): void
    {
        Http::preventStrayRequests();
        config([
            'services.openai.key' => 'sk-test',
            'services.spotify.client_id' => 'client-id',
            'services.spotify.client_secret' => 'client-secret',
            'services.youtube.key' => null,
        ]);
    }

    /**
     * Fake OpenAI (mood profile + song suggestions) and Spotify (token + search).
     *
     * @param  list<array{title: string, artist: string}>  $songs
     * @param  list<array{value: string, label: string, description: string}>  $catalog  what a catalog search finds
     */
    protected function fakeApis(array $songs = [['title' => 'Dreams', 'artist' => 'Fleetwood Mac'], ['title' => 'Harvest Moon', 'artist' => 'Neil Young']], array $catalog = []): void
    {
        Http::fake([
            'api.openai.com/v1/responses' => function (Request $request) use ($songs, $catalog) {
                $answer = match ($request['text']['format']['name']) {
                    'mood_interpretation' => [
                        'dayTime' => 'dusk',
                        'moods' => [
                            ['value' => 'Wistful', 'label' => 'Struggente', 'description' => 'Un rimpianto dolce e malinconico.'],
                            ['value' => 'nostalgic', 'label' => 'Nostalgico', 'description' => 'Ignored: built-in moods use the language files.'],
                        ],
                        'relationship' => 'Lovers',
                        'genres' => [['value' => 'Soft Rock', 'label' => 'Soft rock', 'description' => 'Rock morbido e melodico.']],
                        'energy' => 23, 'popularity' => 140, 'instrumentalness' => 81,
                    ],
                    'catalog_search' => ['results' => $catalog],
                    default => ['tracks' => $songs],
                };

                return Http::response(['status' => 'completed', 'output' => [
                    ['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode($answer)]]],
                ]]);
            },
            'accounts.spotify.com/*' => Http::response(['access_token' => 'app-token', 'expires_in' => 3600]),
            'api.spotify.com/v1/search*' => function (Request $request) {
                preg_match('/track:"(.+?)" artist:"(.+?)"/', $request['q'], $match);

                return Http::response(['tracks' => ['items' => $match === [] ? [] : [[
                    'id' => md5($match[1]),
                    'name' => $match[1],
                    'duration_ms' => 200000,
                    'artists' => [['name' => $match[2]]],
                    'album' => ['name' => 'Album', 'images' => []],
                ]]]]);
            },
        ]);
    }
}
