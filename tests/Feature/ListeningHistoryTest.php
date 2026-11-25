<?php

namespace Tests\Feature;

use App\Livewire\MoodComposer;
use App\Models\Guest;
use App\Models\PromptHistory;
use App\Models\User;
use App\Services\ListeningHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Concerns\FakesMusicServices;
use Tests\TestCase;

class ListeningHistoryTest extends TestCase
{
    use FakesMusicServices, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureMusicServices();
        $this->fakeApis();
    }

    public function test_a_guest_gets_a_token_and_asking_the_same_prompt_again_avoids_the_songs_already_given(): void
    {
        $this->composeFor('sunset with my girlfriend');

        $guest = Guest::sole();
        $this->assertSame(64, strlen($guest->token));
        $this->assertSame(['Dreams - Fleetwood Mac', 'Harvest Moon - Neil Young'], PromptHistory::sole()->songs);
        $this->assertNull(PromptHistory::sole()->user_id);

        $this->composeFor('  Sunset with my GIRLFRIEND ', cookie: $guest->token);

        $this->assertSame(1, Guest::count());
        $this->assertSame(2, $guest->promptHistories()->count());
        Http::assertSent(fn (Request $request): bool => $this->asksToAvoid($request, 'Dreams - Fleetwood Mac'));
    }

    public function test_a_different_prompt_or_another_guest_starts_from_scratch(): void
    {
        $this->composeFor('sunset with my girlfriend');
        $token = Guest::sole()->token;

        $this->composeFor('rainy morning commute', cookie: $token);
        $this->composeFor('sunset with my girlfriend', cookie: 'someone-else');

        Http::assertNotSent(fn (Request $request): bool => $this->asksToAvoid($request, 'Dreams - Fleetwood Mac'));
    }

    public function test_signed_in_players_without_a_subscription_are_remembered_by_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->composeFor('sunset with my girlfriend');
        $this->composeFor('sunset with my girlfriend');

        $this->assertSame(0, Guest::count());
        $this->assertSame(2, PromptHistory::where('user_id', $user->id)->count());
        Http::assertSent(fn (Request $request): bool => $this->asksToAvoid($request, 'Dreams - Fleetwood Mac'));
    }

    public function test_subscribers_are_left_out(): void
    {
        $this->actingAs(User::factory()->subscribed()->create());
        $this->composeFor('sunset with my girlfriend');
        $this->composeFor('sunset with my girlfriend');

        $this->assertSame(0, PromptHistory::count());
        Http::assertNotSent(fn (Request $request): bool => $this->asksToAvoid($request, 'Dreams - Fleetwood Mac'));
    }

    public function test_old_history_and_the_guests_left_without_any_are_pruned(): void
    {
        $kept = Guest::factory()->create();
        $forgotten = Guest::factory()->create(['created_at' => now()->subDays(PromptHistory::RETENTION_DAYS + 1)]);
        $fresh = Guest::factory()->create();
        $prompt = ['phrase' => 'x', 'dayTime' => 'Dusk', 'moods' => [], 'relationship' => 'Alone', 'genres' => [], 'energy' => 1, 'popularity' => 1, 'instrumentalness' => 1];

        $old = PromptHistory::create(['guest_id' => $forgotten->id, 'fingerprint' => 'a', 'prompt' => $prompt, 'songs' => []]);
        $old->forceFill(['created_at' => now()->subDays(PromptHistory::RETENTION_DAYS + 1)])->save();
        PromptHistory::create(['guest_id' => $kept->id, 'fingerprint' => 'b', 'prompt' => $prompt, 'songs' => []]);

        $this->artisan('model:prune', ['--model' => [PromptHistory::class]])->assertSuccessful();
        $this->artisan('model:prune', ['--model' => [Guest::class]])->assertSuccessful();

        $this->assertSame(1, PromptHistory::count());
        $this->assertModelExists($kept);
        $this->assertModelExists($fresh);
        $this->assertModelMissing($forgotten);
    }

    /**
     * Read a phrase and compose a list in a fresh visit, as a returning visitor would.
     */
    private function composeFor(string $phrase, ?string $cookie = null): void
    {
        Livewire::withCookies($cookie === null ? [] : [ListeningHistory::COOKIE => $cookie])
            ->test(MoodComposer::class)
            ->set('phrase', $phrase)
            ->call('interpret')
            ->call('compose')
            ->assertHasNoErrors()
            ->assertCount('tracks', 2);
    }

    private function asksToAvoid(Request $request, string $song): bool
    {
        return ($request['text']['format']['name'] ?? null) === 'track_suggestions'
            && str_contains($request['input'], "- {$song}");
    }
}
