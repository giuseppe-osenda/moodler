<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\App;
use RuntimeException;

/**
 * Prepares the Paddle overlay checkout for the monthly subscription.
 *
 * It registers the player as a Paddle customer on first use, which calls Paddle's API,
 * so run it only when the player asks to subscribe.
 */
class StartSubscriptionCheckout
{
    /**
     * Options for `Paddle.Checkout.open`.
     *
     * @return array{settings: array<string, mixed>, items: list<array{priceId: string, quantity: int}>, customer?: array{id: string}, customData?: array<string, mixed>}
     *
     * @throws RuntimeException when Paddle is not configured
     */
    public function __invoke(User $user, string $returnTo): array
    {
        $priceId = config('services.paddle.price_id');

        if (blank($priceId) || blank(config('cashier.client_side_token')) || blank(config('cashier.api_key'))) {
            throw new RuntimeException('Paddle is not configured: set PADDLE_CLIENT_SIDE_TOKEN, PADDLE_API_KEY and PADDLE_PRICE_ID.');
        }

        $options = $user->subscribe($priceId)->returnTo($returnTo)->options();

        $options['settings'] = [
            'displayMode' => 'overlay',
            'successUrl' => $returnTo,
            'allowLogout' => false,
            'locale' => App::getLocale(),
        ];

        return $options;
    }
}
