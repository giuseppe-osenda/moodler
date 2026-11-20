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
     * @param  list<array{value: string, label: string, description: string}>  $catalog  what a catalog search finds
     */
    private function fakeApis(array $songs = [['title' => 'Dreams', 'artist' => 'Fleetwood Mac'], ['title' => 'Harvest Moon', 'artist' => 'Neil Young']], array $catalog = []): void
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

    public function test_the_home_page_shows_the_first_step(): void
    {
        $this->withoutVite()->get('/', ['Accept-Language' => ''])->assertOk()->assertSeeLivewire(MoodComposer::class)->assertSee(__('moodler.phrase.title'), false);
    }

    public function test_reading_a_phrase_fills_the_atmosphere_and_knobs_and_keeps_custom_labels_selectable(): void
    {
        $this->fakeApis();

        Livewire::test(MoodComposer::class)
            ->set('phrase', 'sunset with my girlfriend')
            ->call('interpret')
            ->assertHasNoErrors()
            ->assertSet('step', MoodComposer::STEP_ATMOSPHERE)
            ->assertSet('dayTime', 'Dusk')
            ->assertSet('moods', ['Wistful', 'Nostalgic'])
            ->assertSet('genres', ['Soft Rock'])
            ->assertSet('energy', 25)
            ->assertSet('popularity', 100)
            ->assertSet('instrumentalness', 80)
            ->assertSee('Struggente')
            ->assertSee(__('moodler.options.Melancholic'))
            ->assertSee(__('moodler.options.Focused'));
    }

    public function test_custom_moods_and_genres_from_the_ai_arrive_translated_and_described(): void
    {
        $this->fakeApis();

        Livewire::test(MoodComposer::class)
            ->set('phrase', 'tramonto con la mia ragazza')
            ->call('interpret')
            ->assertSet('customOptions', [
                'Wistful' => ['label' => 'Struggente', 'description' => 'Un rimpianto dolce e malinconico.'],
                'Soft Rock' => ['label' => 'Soft rock', 'description' => 'Rock morbido e melodico.'],
            ])
            ->assertSeeHtml('data-tooltip="Un rimpianto dolce e malinconico."')
            ->assertSeeHtml('data-tooltip="Rock morbido e melodico."')
            ->assertSee(__('moodler.options.Nostalgic'))
            ->assertDontSee('Ignored: built-in moods use the language files.');

        Http::assertSent(fn (Request $request): bool => ($request['text']['format']['name'] ?? null) === 'mood_interpretation'
            && str_contains($request['input'], 'one sentence in '.config('app.supported_locales.'.app()->getLocale())));
    }

    public function test_a_phrase_is_required(): void
    {
        Livewire::test(MoodComposer::class)
            ->set('phrase', '')
            ->call('interpret')
            ->assertHasErrors(['phrase' => 'required'])
            ->assertSet('step', MoodComposer::STEP_PHRASE);
    }

    public function test_only_the_moment_is_shown_until_the_mood_is_read(): void
    {
        Livewire::test(MoodComposer::class)
            ->assertSee(__('moodler.phrase.title'))
            ->assertDontSee(__('moodler.atmosphere.title'))
            ->call('revealTuning')
            ->assertSet('step', MoodComposer::STEP_PHRASE)
            ->assertDontSee(__('moodler.tuning.title'));
    }

    public function test_sections_appear_one_after_another_and_stay_on_screen(): void
    {
        $this->fakeApis();

        Livewire::test(MoodComposer::class)
            ->set('phrase', 'sunset with my girlfriend')
            ->call('interpret')
            ->assertDispatched('section-revealed', section: 'atmosphere')
            ->assertSee([__('moodler.phrase.title'), __('moodler.atmosphere.title'), __('moodler.atmosphere.next')])
            ->assertDontSee(__('moodler.tuning.title'))
            ->assertDontSee(__('moodler.actions.create'))
            ->call('revealTuning')
            ->assertDispatched('section-revealed', section: 'tuning')
            ->assertSee([__('moodler.phrase.title'), __('moodler.atmosphere.title'), __('moodler.tuning.title'), __('moodler.actions.create')])
            ->assertDontSee(__('moodler.atmosphere.next'))
            ->call('interpret')
            ->assertNotDispatched('section-revealed')
            ->assertSet('step', MoodComposer::STEP_TUNING);
    }

    public function test_composing_shows_the_player_with_the_matched_tracks(): void
    {
        $this->fakeApis();

        Livewire::test(MoodComposer::class)
            ->set('phrase', 'sunset with my girlfriend')
            ->call('interpret')
            ->call('revealTuning')
            ->set('energy', 20)
            ->call('compose')
            ->assertHasNoErrors()
            ->assertCount('tracks', 2)
            ->assertSet('tracks.0.uri', 'spotify:track:'.md5('Dreams'))
            ->assertDispatched('tracks-updated', uris: ['spotify:track:'.md5('Dreams'), 'spotify:track:'.md5('Harvest Moon')])
            ->assertSee(__('moodler.player.your_list'))
            ->assertSee('Harvest Moon')
            ->assertSee(__('moodler.actions.remix'));

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
            ->assertSee(__('moodler.actions.pending'))
            ->call('compose')
            ->assertSet('hasPendingChanges', false);

        $this->assertContains('Dreams - Fleetwood Mac', $component->get('heardSongs'));
        Http::assertSent(fn (Request $request): bool => ($request['text']['format']['name'] ?? null) === 'track_suggestions'
            && str_contains($request['input'], 'already heard them')
            && str_contains($request['input'], '- Dreams - Fleetwood Mac'));
    }

    public function test_reading_a_new_phrase_replaces_the_moods_and_genres(): void
    {
        $readings = [
            ['moods' => [['value' => 'Focused', 'label' => '', 'description' => '']], 'genres' => [['value' => 'Lo-Fi', 'label' => '', 'description' => '']], 'energy' => 35],
            ['moods' => [['value' => 'Energetic', 'label' => '', 'description' => '']], 'genres' => [['value' => 'Rock', 'label' => '', 'description' => '']], 'energy' => 80],
        ];

        Http::fake(['api.openai.com/v1/responses' => function () use (&$readings) {
            $answer = ['dayTime' => 'Evening', 'relationship' => 'Alone', 'popularity' => 50, 'instrumentalness' => 20, ...array_shift($readings)];

            return Http::response(['status' => 'completed', 'output' => [
                ['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode($answer)]]],
            ]]);
        }]);

        Livewire::test(MoodComposer::class)
            ->set('phrase', 'work session where I need to focus')
            ->call('interpret')
            ->assertSet('moods', ['Focused'])
            ->set('phrase', 'after-work run to blow off stress')
            ->call('interpret')
            ->assertSet('moods', ['Energetic'])
            ->assertSet('genres', ['Rock'])
            ->assertSet('energy', 80);
    }

    public function test_moods_and_genres_can_be_combined_up_to_the_limit(): void
    {
        $this->fakeApis();

        Livewire::test(MoodComposer::class)
            ->set('phrase', 'sunset with my girlfriend')
            ->call('interpret')
            ->call('toggleOption', 'genres', 'Folk')
            ->call('toggleOption', 'genres', 'Indie')
            ->assertSet('genres', ['Soft Rock', 'Folk', 'Indie'])
            ->call('toggleOption', 'genres', 'Jazz')
            ->assertSet('genres', ['Soft Rock', 'Folk', 'Indie'])
            ->call('toggleOption', 'moods', 'Wistful')
            ->assertSet('moods', ['Nostalgic'])
            ->call('toggleOption', 'moods', 'Nostalgic')
            ->assertSet('moods', ['Nostalgic'])
            ->call('toggleOption', 'relationship', 'Friends')
            ->assertSet('relationship', 'Lovers');
    }

    public function test_a_mixed_list_asks_the_ai_to_blend_moods_and_genres(): void
    {
        $this->fakeApis();

        Livewire::test(MoodComposer::class)
            ->set('phrase', 'sunset with my girlfriend')
            ->call('interpret')
            ->call('toggleOption', 'genres', 'Folk')
            ->call('compose')
            ->assertHasNoErrors()
            ->assertCount('tracks', 2);

        Http::assertSent(fn (Request $request): bool => ($request['text']['format']['name'] ?? null) === 'track_suggestions'
            && str_contains($request['input'], 'blends Wistful + Nostalgic')
            && str_contains($request['input'], 'Genres: Soft Rock, Folk.')
            && str_contains($request['input'], 'bridge two genres'));
    }

    public function test_composing_rejects_more_moods_than_allowed(): void
    {
        $this->fakeApis();

        Livewire::test(MoodComposer::class)
            ->set('phrase', 'sunset with my girlfriend')
            ->call('interpret')
            ->set('moods', ['Joy', 'Sad', 'Angry', 'Tired'])
            ->call('compose')
            ->assertHasErrors(['moods' => 'max'])
            ->assertSet('tracks', []);
    }

    public function test_built_in_mood_and_genre_chips_carry_their_description(): void
    {
        $this->fakeApis();

        Livewire::test(MoodComposer::class)
            ->set('phrase', 'sunset with my girlfriend')
            ->call('interpret')
            ->assertSeeHtml('data-tooltip="'.e(__('moodler.genre_descriptions.Jazz')).'"')
            ->assertSeeHtml('data-tooltip="'.e(__('moodler.mood_descriptions.Focused')).'"');
    }

    public function test_searching_finds_genres_beyond_the_chips_and_picking_one_keeps_its_label_and_description(): void
    {
        $this->fakeApis(catalog: [
            ['value' => 'Rock', 'label' => 'Rock', 'description' => 'Already a chip.'],
            ['value' => 'Shoegaze', 'label' => 'Shoegaze', 'description' => 'Dreamy walls of guitar.'],
            ['value' => 'Dream Pop', 'label' => 'Dream pop', 'description' => 'Hazy, ethereal pop.'],
        ]);

        $component = Livewire::test(MoodComposer::class)
            ->set('phrase', 'sunset with my girlfriend')
            ->call('interpret')
            ->call('searchCatalog', 'genres', '  shoegaze ')
            ->assertSet('catalogSearches.genres.query', 'shoegaze')
            ->assertSee(__('moodler.atmosphere.search.genres.more'))
            ->assertSee('Dream pop');

        $this->assertSame(['Shoegaze', 'Dream Pop'], array_column($component->instance()->catalogResults('genres'), 'value'));

        $component->call('toggleOption', 'genres', 'Dream Pop')
            ->assertSet('genres', ['Soft Rock', 'Dream Pop'])
            ->assertSet('customOptions.Dream Pop', ['label' => 'Dream pop', 'description' => 'Hazy, ethereal pop.'])
            ->assertSeeHtml('data-tooltip="Hazy, ethereal pop."');

        $this->assertSame(['Shoegaze'], array_column($component->instance()->catalogResults('genres'), 'value'));
    }

    public function test_short_searches_do_not_call_the_ai(): void
    {
        $this->fakeApis();

        Livewire::test(MoodComposer::class)
            ->set('phrase', 'sunset with my girlfriend')
            ->call('interpret')
            ->call('searchCatalog', 'moods', 'a')
            ->assertSet('catalogSearches.moods', ['query' => 'a', 'results' => [], 'failed' => false])
            ->call('searchCatalog', 'relationship', 'friends');

        Http::assertNotSent(fn (Request $request): bool => ($request['text']['format']['name'] ?? null) === 'catalog_search');
    }

    public function test_a_failed_search_is_flagged(): void
    {
        Http::fake(['api.openai.com/*' => Http::response(['error' => ['message' => 'boom']], 500)]);

        Livewire::test(MoodComposer::class)
            ->call('searchCatalog', 'moods', 'malinconia')
            ->assertSet('catalogSearches.moods.failed', true)
            ->assertSet('catalogSearches.moods.results', []);
    }

    public function test_changing_language_clears_search_results(): void
    {
        $this->fakeApis(catalog: [['value' => 'Shoegaze', 'label' => 'Shoegaze', 'description' => 'Dreamy.']]);

        Livewire::test(MoodComposer::class)
            ->call('searchCatalog', 'genres', 'shoegaze')
            ->assertCount('catalogSearches.genres.results', 1)
            ->set('locale', 'en')
            ->assertSet('catalogSearches.genres.results', []);
    }

    public function test_it_explains_when_no_song_can_be_found(): void
    {
        $this->fakeApis(songs: []);

        Livewire::test(MoodComposer::class)
            ->set('phrase', 'sunset with my girlfriend')
            ->call('interpret')
            ->call('compose')
            ->assertSet('tracks', [])
            ->assertSee(__('moodler.errors.no_tracks'));
    }

    public function test_it_shows_a_friendly_error_when_a_service_fails(): void
    {
        Http::fake(['api.openai.com/*' => Http::response(['error' => ['message' => 'boom']], 500)]);

        Livewire::test(MoodComposer::class)
            ->set('phrase', 'sunset with my girlfriend')
            ->call('interpret')
            ->assertSet('step', MoodComposer::STEP_PHRASE)
            ->assertSee(__('moodler.errors.interpret'));
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
            ->assertDontSee(__('moodler.player.your_list'));
    }
}
