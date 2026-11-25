<?php

namespace Tests\Feature;

use App\Livewire\Account;
use App\Livewire\MoodComposer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\Concerns\FakesMusicServices;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use FakesMusicServices, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureMusicServices();
        $this->fakeApis();
        config([
            'cashier.sandbox' => true,
            'cashier.api_key' => 'paddle-api-key',
            'cashier.client_side_token' => 'test_client_token',
            'services.paddle.price_id' => 'pri_monthly',
        ]);
    }

    public function test_guests_sign_in_before_subscribing(): void
    {
        $this->composedList()->call('startCheckout')->assertRedirect(route('login'));
    }

    public function test_players_confirm_their_email_before_subscribing(): void
    {
        $this->actingAs(User::factory()->unverified()->create());

        $this->composedList()->call('startCheckout')->assertRedirect(route('verification.notice'));
        $this->assertSame(route('home', ['restore' => 1]), session('url.intended'));
    }

    public function test_the_checkout_opens_as_an_overlay_for_the_monthly_price_and_comes_back_to_the_list(): void
    {
        $this->fakePaddleCustomer();
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->composedList()
            ->call('startCheckout')
            ->assertSet('errorKey', null)
            ->assertDispatched('paddle-checkout', function (string $event, array $params): bool {
                $options = $params['options'];

                return $options['settings']['displayMode'] === 'overlay'
                    && $options['settings']['successUrl'] === route('home', ['restore' => 1, 'subscribed' => 1])
                    && $options['items'] === [['priceId' => 'pri_monthly', 'quantity' => 1]]
                    && $options['customer'] === ['id' => 'ctm_123'];
            });

        $this->assertSame('ctm_123', $user->fresh()->customer->paddle_id);
    }

    public function test_a_missing_configuration_or_a_paddle_failure_shows_an_error(): void
    {
        $this->actingAs(User::factory()->create());
        config(['services.paddle.price_id' => null]);

        $this->composedList()->call('startCheckout')->assertSet('errorKey', 'checkout')->assertNotDispatched('paddle-checkout')
            ->assertSee(__('moodler.errors.checkout'));

        config(['services.paddle.price_id' => 'pri_monthly']);
        Http::fake(['sandbox-api.paddle.com/*' => Http::response(['error' => ['type' => 'request_error', 'code' => 'forbidden', 'detail' => 'Nope']], 403)]);

        $this->composedList()->call('startCheckout')->assertSet('errorKey', 'checkout')->assertNotDispatched('paddle-checkout');
    }

    public function test_after_paying_the_page_waits_for_paddle_to_activate_the_subscription(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Livewire::withQueryParams(['subscribed' => 1])->test(MoodComposer::class)
            ->assertSet('subscriptionState', 'activating')
            ->assertSee(__('moodler.checkout.activating'))
            ->call('checkSubscription')
            ->assertSet('subscriptionState', 'activating');

        $user->subscriptions()->create(['type' => 'default', 'paddle_id' => 'sub_1', 'status' => 'active']);

        $component->call('checkSubscription')->assertSet('subscriptionState', 'active')->assertSee(__('moodler.checkout.active'));
    }

    public function test_it_stops_waiting_after_a_minute(): void
    {
        $this->actingAs(User::factory()->create());

        $component = Livewire::withQueryParams(['subscribed' => 1])->test(Account::class);

        for ($check = 0; $check < 30; $check++) {
            $component->call('checkSubscription');
        }

        $component->assertSet('subscriptionState', 'delayed')->assertSee(__('moodler.checkout.delayed'));
    }

    public function test_the_account_page_offers_the_subscription_or_shows_it(): void
    {
        $this->actingAs(User::factory()->create())->withoutVite()->get(route('account'))
            ->assertOk()
            ->assertSee(__('moodler.account.pitch'));

        $this->actingAs(User::factory()->subscribed()->create())->withoutVite()->get(route('account'))
            ->assertOk()
            ->assertSee(__('moodler.account.status.active'))
            ->assertSee(__('moodler.account.cancel'));
    }

    public function test_canceling_keeps_the_subscription_until_the_paid_month_ends_and_can_be_taken_back(): void
    {
        $user = User::factory()->subscribed()->create();
        $subscription = $user->subscription();
        $this->actingAs($user);
        Http::fake([
            "sandbox-api.paddle.com/subscriptions/{$subscription->paddle_id}/cancel" => Http::response(['data' => [
                'status' => 'active',
                'scheduled_change' => ['action' => 'cancel', 'effective_at' => now()->addDays(20)->toIso8601String()],
            ]]),
            "sandbox-api.paddle.com/subscriptions/{$subscription->paddle_id}" => Http::response(['data' => ['status' => 'active']]),
        ]);

        Livewire::test(Account::class)
            ->call('cancel')
            ->assertSet('errorKey', null)
            ->assertSee(__('moodler.account.status.canceled'))
            ->assertSee(__('moodler.account.resume'));

        $this->assertTrue($subscription->fresh()->onGracePeriod());
        $this->assertTrue($user->fresh()->canSavePlaylists());

        Livewire::test(Account::class)->call('resume')->assertSet('errorKey', null);

        $this->assertNull($subscription->fresh()->ends_at);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PATCH' && $request['scheduled_change'] === null);
    }

    public function test_a_failed_cancelation_is_reported_to_the_player(): void
    {
        $this->actingAs(User::factory()->subscribed()->create());
        Http::fake(['sandbox-api.paddle.com/*' => Http::response(['error' => ['type' => 'api_error', 'code' => 'internal_error', 'detail' => 'Boom']], 500)]);

        Livewire::test(Account::class)
            ->call('cancel')
            ->assertSet('errorKey', 'cancel')
            ->assertSee(__('moodler.account.errors.cancel'));
    }

    private function fakePaddleCustomer(): void
    {
        Http::fake([
            'sandbox-api.paddle.com/customers*' => fn (Request $request) => $request->method() === 'GET'
                ? Http::response(['data' => []])
                : Http::response(['data' => ['id' => 'ctm_123', 'name' => $request['name'], 'email' => $request['email']]], 201),
        ]);
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
