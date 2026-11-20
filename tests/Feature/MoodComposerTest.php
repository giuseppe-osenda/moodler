<?php

namespace Tests\Feature;

use App\Livewire\MoodComposer;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class MoodComposerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config([
            'services.openai.key' => 'sk-test',
            'services.spotify.client_id' => 'client-id',
            'services.spotify.client_secret' => 'client-secret',
        ]);
    }

    /**
     * Fake OpenAI (mood profile + song suggestions) and Spotify (token + search).
     *
     * @param  list<array{title: string, artist: string}>  $songs
     */
    private function fakeApis(array $songs = [['title' => 'Dreams', 'artist' => 'Fleetwood Mac'], ['title' => 'Harvest Moon', 'artist' => 'Neil Young']]): void
    {
        Http::fake([
            'api.openai.com/v1/responses' => function (Request $request) use ($songs) {
                $answer = $request['text']['format']['name'] === 'mood_profile'
                    ? ['dayTime' => 'Dusk', 'mood' => 'Wistful', 'relationship' => 'Lovers', 'genre' => 'Soft Rock']
                    : ['tracks' => $songs];

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

    public function test_the_home_page_shows_the_first_step(): void
    {
        $this->withoutVite()->get('/')->assertOk()->assertSeeLivewire(MoodComposer::class)->assertSee("What's the moment?", false);
    }

    public function test_reading_a_phrase_fills_the_atmosphere_and_keeps_custom_labels_selectable(): void
    {
        $this->fakeApis();

        Livewire::test(MoodComposer::class)
            ->set('phrase', 'sunset with my girlfriend')
            ->call('interpret')
            ->assertHasNoErrors()
            ->assertSet('step', MoodComposer::STEP_ATMOSPHERE)
            ->assertSet('mood', 'Wistful')
            ->assertSet('genre', 'Soft Rock')
            ->assertSee('Wistful')
            ->assertSee('Melancholic');
    }

    public function test_a_phrase_is_required(): void
    {
        Livewire::test(MoodComposer::class)
            ->set('phrase', '')
            ->call('interpret')
            ->assertHasErrors(['phrase' => 'required'])
            ->assertSet('step', MoodComposer::STEP_PHRASE);
    }

    public function test_later_steps_are_locked_until_the_mood_is_read(): void
    {
        Livewire::test(MoodComposer::class)
            ->call('goToStep', MoodComposer::STEP_TUNING)
            ->assertSet('step', MoodComposer::STEP_PHRASE);
    }

    public function test_composing_shows_the_player_with_the_matched_tracks(): void
    {
        $this->fakeApis();

        Livewire::test(MoodComposer::class)
            ->set('phrase', 'sunset with my girlfriend')
            ->call('interpret')
            ->call('goToStep', MoodComposer::STEP_TUNING)
            ->set('energy', 20)
            ->call('compose')
            ->assertHasNoErrors()
            ->assertCount('tracks', 2)
            ->assertSet('tracks.0.uri', 'spotify:track:'.md5('Dreams'))
            ->assertDispatched('tracks-updated', uris: ['spotify:track:'.md5('Dreams'), 'spotify:track:'.md5('Harvest Moon')])
            ->assertSee('Your list')
            ->assertSee('Harvest Moon')
            ->assertSee('Remix');

        Http::assertSent(fn (Request $request): bool => ($request['text']['format']['name'] ?? null) === 'track_suggestions'
            && str_contains($request['input'], 'Energy: mellow and laid-back'));
    }

    public function test_remixing_asks_to_avoid_songs_already_heard_and_flags_pending_changes(): void
    {
        $this->fakeApis();

        $component = Livewire::test(MoodComposer::class)
            ->set('phrase', 'sunset with my girlfriend')
            ->call('interpret')
            ->call('compose')
            ->assertSet('hasPendingChanges', false)
            ->set('popularity', 90)
            ->assertSet('hasPendingChanges', true)
            ->assertSee('remix to hear them')
            ->call('compose')
            ->assertSet('hasPendingChanges', false);

        $this->assertContains('Dreams - Fleetwood Mac', $component->get('heardSongs'));
        Http::assertSent(fn (Request $request): bool => ($request['text']['format']['name'] ?? null) === 'track_suggestions'
            && str_contains($request['input'], 'already heard them')
            && str_contains($request['input'], '- Dreams - Fleetwood Mac'));
    }

    public function test_it_explains_when_no_song_can_be_found(): void
    {
        $this->fakeApis(songs: []);

        Livewire::test(MoodComposer::class)
            ->set('phrase', 'sunset with my girlfriend')
            ->call('interpret')
            ->call('compose')
            ->assertSet('tracks', [])
            ->assertSee('No songs matched this mix');
    }

    public function test_it_shows_a_friendly_error_when_a_service_fails(): void
    {
        Http::fake(['api.openai.com/*' => Http::response(['error' => ['message' => 'boom']], 500)]);

        Livewire::test(MoodComposer::class)
            ->set('phrase', 'sunset with my girlfriend')
            ->call('interpret')
            ->assertSet('step', MoodComposer::STEP_PHRASE)
            ->assertSee('We could not read your mood right now');
    }

    public function test_starting_over_clears_everything(): void
    {
        $this->fakeApis();

        Livewire::test(MoodComposer::class)
            ->set('phrase', 'sunset with my girlfriend')
            ->call('interpret')
            ->call('compose')
            ->call('restart')
            ->assertSet('step', MoodComposer::STEP_PHRASE)
            ->assertSet('phrase', '')
            ->assertSet('tracks', [])
            ->assertDontSee('Your list');
    }
}
