<?php

namespace Tests\Feature;

use App\Livewire\MoodComposer;
use App\Livewire\PlaylistLibrary;
use App\Models\Playlist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\Concerns\FakesMusicServices;
use Tests\TestCase;

class PlaylistTest extends TestCase
{
    use FakesMusicServices, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureMusicServices();
        $this->fakeApis();
    }

    public function test_guests_are_invited_to_sign_in_and_come_back_to_their_list(): void
    {
        $this->composedList()
            ->assertSee(__('moodler.save.guest_pitch'))
            ->call('savePlaylist')
            ->assertRedirect(route('login'));

        $this->assertSame(route('home', ['restore' => 1]), session('url.intended'));
        $this->assertSame(0, Playlist::count());
    }

    public function test_after_signing_in_the_list_composed_as_a_guest_is_restored(): void
    {
        $this->composedList();

        $this->actingAs(User::factory()->create());

        Livewire::withQueryParams(['restore' => 1])->test(MoodComposer::class)
            ->assertSet('phrase', 'sunset with my girlfriend')
            ->assertCount('tracks', 2)
            ->assertSet('step', MoodComposer::STEP_TUNING)
            ->assertSet('hasPendingChanges', false)
            ->assertSee('Harvest Moon');
    }

    public function test_signed_in_players_without_a_subscription_are_offered_one(): void
    {
        $this->actingAs(User::factory()->create());

        $this->composedList()
            ->assertSee(__('moodler.save.subscribe_pitch'))
            ->call('savePlaylist')
            ->assertNoRedirect();

        $this->assertSame(0, Playlist::count());
    }

    public function test_a_playlist_without_a_name_is_named_after_the_phrase(): void
    {
        $user = User::factory()->subscribed()->create();
        $this->actingAs($user);

        $this->composedList()
            ->assertSee(__('moodler.save.save'))
            ->call('savePlaylist', true)
            ->assertHasNoErrors()
            ->assertSet('playlistNotice', 'saved')
            ->assertSet('playlistName', 'sunset with my girlfriend');

        $playlist = $user->playlists()->sole();
        $this->assertSame('sunset with my girlfriend', $playlist->name);
        $this->assertSame('sunset with my girlfriend', $playlist->phrase);
        $this->assertSame(['Wistful', 'Nostalgic'], $playlist->moods);
        $this->assertSame(['Soft Rock'], $playlist->genres);
        $this->assertSame('Struggente', $playlist->custom_options['Wistful']['label']);
        $this->assertSame(['Dreams', 'Harvest Moon'], array_column($playlist->tracks, 'title'));
    }

    public function test_the_saved_settings_are_those_the_list_was_composed_with(): void
    {
        $user = User::factory()->subscribed()->create();
        $this->actingAs($user);

        $component = $this->composedList();
        $composedEnergy = $component->get('energy');

        $component->set('energy', 95)->set('playlistName', 'Tramonto')->call('savePlaylist', true);

        $this->assertNotSame(95, $composedEnergy);
        $this->assertSame($composedEnergy, $user->playlists()->sole()->energy);
        $this->assertSame('Tramonto', $user->playlists()->sole()->name);
    }

    public function test_an_opened_playlist_can_be_reshaped_and_overwritten_or_saved_as_a_new_one(): void
    {
        $user = User::factory()->subscribed()->create();
        $playlist = Playlist::factory()->for($user)->create(['name' => 'Jazz serale', 'phrase' => 'sunset with my girlfriend']);
        $this->actingAs($user);

        $component = Livewire::test(MoodComposer::class, ['playlist' => $playlist])
            ->assertSet('playlistId', $playlist->id)
            ->assertSet('playlistName', 'Jazz serale')
            ->assertSet('tracks.0.title', 'So What')
            ->assertSee(__('moodler.save.overwrite', ['name' => 'Jazz serale']))
            ->assertSee(__('moodler.save.as_new'))
            ->call('compose')
            ->call('savePlaylist', false)
            ->assertSet('playlistNotice', 'updated');

        $playlist->refresh();
        $this->assertSame('Jazz serale', $playlist->name);
        $this->assertSame(['Dreams', 'Harvest Moon'], array_column($playlist->tracks, 'title'));

        $component->set('playlistName', 'Jazz serale bis')->call('savePlaylist', true)->assertSet('playlistNotice', 'saved');

        $this->assertSame(2, $user->playlists()->count());
        $this->assertSame('Jazz serale', $playlist->fresh()->name);
        $this->assertNotSame($playlist->id, $component->get('playlistId'));
    }

    public function test_playlist_names_have_a_length_limit(): void
    {
        $this->actingAs(User::factory()->subscribed()->create());

        $this->composedList()
            ->set('playlistName', str_repeat('a', Playlist::MAX_NAME_LENGTH + 1))
            ->call('savePlaylist', true)
            ->assertHasErrors('playlistName');
    }

    public function test_nobody_opens_someone_elses_playlist(): void
    {
        $playlist = Playlist::factory()->create();

        $this->actingAs(User::factory()->subscribed()->create())
            ->get(route('playlists.show', $playlist))
            ->assertForbidden();
    }

    public function test_opening_a_playlist_needs_an_active_subscription(): void
    {
        $user = User::factory()->create();
        $playlist = Playlist::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('playlists.show', $playlist))
            ->assertRedirect(route('account'));
    }

    public function test_playlist_pages_need_a_signed_in_player_with_a_confirmed_email(): void
    {
        $this->get(route('playlists.index'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->unverified()->create())
            ->get(route('playlists.index'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_the_library_lists_only_the_players_playlists_and_deletes_them(): void
    {
        $user = User::factory()->subscribed()->create();
        $mine = Playlist::factory()->for($user)->create(['name' => 'Mia playlist']);
        $theirs = Playlist::factory()->create(['name' => 'Playlist altrui']);
        $this->actingAs($user);

        $this->withoutVite()->get(route('playlists.index'))
            ->assertOk()
            ->assertSee('Mia playlist')
            ->assertSee(route('playlists.show', $mine))
            ->assertDontSee('Playlist altrui');

        Livewire::test(PlaylistLibrary::class)
            ->call('delete', $theirs->id)
            ->call('delete', $mine->id)
            ->assertSee(__('moodler.library.empty'));

        $this->assertModelMissing($mine);
        $this->assertModelExists($theirs);
    }

    public function test_without_a_subscription_the_library_keeps_playlists_but_does_not_open_them(): void
    {
        $user = User::factory()->create();
        $playlist = Playlist::factory()->for($user)->create(['name' => 'Mia playlist']);

        $this->actingAs($user)->withoutVite()->get(route('playlists.index'))
            ->assertOk()
            ->assertSee('Mia playlist')
            ->assertSee(__('moodler.library.subscription_needed'))
            ->assertDontSee(route('playlists.show', $playlist));
    }

    private function composedList(): Testable
    {
        return Livewire::test(MoodComposer::class)
            ->set('phrase', 'sunset with my girlfriend')
            ->call('interpret')
            ->call('compose')
            ->assertCount('tracks', 2);
    }
}
