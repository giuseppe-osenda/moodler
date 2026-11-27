<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialUser;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config(['services.google.client_id' => null]);
    }

    public function test_the_sign_in_screens_are_shown_in_the_players_language(): void
    {
        $this->withCookie('locale', 'it')->get(route('login'))->assertOk()->assertSee('Bentornato');
        $this->withCookie('locale', 'en')->get(route('register'))->assertOk()->assertSee('Create your account');
        $this->withCookie('locale', 'de')->get(route('password.request'))->assertOk()->assertSee('Passwort zurücksetzen');
    }

    public function test_the_phone_menu_holds_the_languages_and_the_account_links(): void
    {
        $guestMenu = $this->phoneMenu($this->withCookie('locale', 'it')->get(route('home'))->assertOk()->getContent());

        $this->assertStringContainsString('aria-label="Apri il menu"', $guestMenu['button']);
        $this->assertStringContainsString(route('login'), $guestMenu['panel']);
        $this->assertMatchesRegularExpression('/aria-pressed="true"\s+lang="it"/', $guestMenu['panel']);
        $this->assertStringContainsString('lang="de"', $guestMenu['panel']);

        $playerMenu = $this->phoneMenu($this->actingAs(User::factory()->create())->get(route('home'))->getContent());

        $this->assertStringNotContainsString(route('login'), $playerMenu['panel']);
        $this->assertStringContainsString(route('playlists.index'), $playerMenu['panel']);
        $this->assertStringContainsString(route('account'), $playerMenu['panel']);
        $this->assertStringContainsString(route('logout'), $playerMenu['panel']);
    }

    public function test_social_buttons_appear_only_for_configured_providers(): void
    {
        $this->get(route('login'))->assertDontSee(route('social.redirect', 'google'));

        config(['services.google.client_id' => 'google-client']);

        $this->get(route('login'))
            ->assertSee(route('social.redirect', 'google'))
            ->assertDontSee(route('social.redirect', 'apple'));
        $this->get(route('social.redirect', 'apple'))->assertNotFound();
    }

    public function test_players_sign_up_confirm_their_email_and_go_back_where_they_were(): void
    {
        Notification::fake();

        $this->withSession(['url.intended' => route('home', ['restore' => 1])])
            ->post(route('register'), [
                'name' => 'Ada',
                'email' => 'ada@example.com',
                'password' => 'a-long-password',
                'password_confirmation' => 'a-long-password',
            ])
            ->assertRedirect(route('home', ['restore' => 1]));

        $user = User::sole();
        $this->assertAuthenticatedAs($user);
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_players_sign_in_with_email_and_password(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com']);

        $this->post(route('login'), ['email' => 'ada@example.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post(route('login'), ['email' => 'ada@example.com', 'password' => 'password'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_new_google_player_gets_a_confirmed_account(): void
    {
        $this->fakeSocialUser('google', id: 'g-1', email: 'ada@example.com', name: 'Ada');

        $this->get(route('social.callback', 'google'))->assertRedirect(route('home'));

        $user = User::sole();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('g-1', $user->google_id);
        $this->assertSame('Ada', $user->name);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertNull($user->password);
    }

    public function test_signing_in_with_google_links_the_account_with_the_same_email(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'ada@example.com']);
        $this->fakeSocialUser('google', id: 'g-1', email: 'ada@example.com', name: 'Ada');

        $this->get(route('social.callback', 'google'))->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame('g-1', $user->fresh()->google_id);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertSame(1, User::count());
    }

    public function test_a_failed_or_emailless_social_sign_in_goes_back_to_the_login_screen(): void
    {
        $this->fakeSocialUser('google', id: 'g-new', email: null, name: null);
        $this->get(route('social.callback', 'google'))->assertRedirect(route('login'))->assertSessionHasErrors('email');

        config(['services.google.client_id' => 'google-client']);
        Socialite::shouldReceive('driver')->with('google')->andThrow(new RuntimeException('Invalid state'));
        $this->get(route('social.callback', 'google'))->assertRedirect(route('login'))->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    private function fakeSocialUser(string $provider, string $id, ?string $email, ?string $name): void
    {
        config(["services.{$provider}.client_id" => "{$provider}-client"]);

        $socialUser = (new SocialUser)->map(['id' => $id, 'email' => $email, 'name' => $name]);
        $driver = Mockery::mock();
        $driver->shouldReceive('user')->andReturn($socialUser);

        Socialite::shouldReceive('driver')->with($provider)->andReturn($driver);
    }

    /**
     * Split a page into the menu button and the phone menu panel it opens.
     *
     * @return array{button: string, panel: string}
     */
    private function phoneMenu(string $html): array
    {
        $this->assertMatchesRegularExpression('/<button[^>]*aria-controls="mobile-menu"[^>]*>/s', $html);
        preg_match('/<button[^>]*aria-controls="mobile-menu"[^>]*>/s', $html, $button);
        preg_match('/<div\s+id="mobile-menu".*?<\/header>/s', $html, $panel);

        return ['button' => $button[0], 'panel' => $panel[0] ?? ''];
    }
}
