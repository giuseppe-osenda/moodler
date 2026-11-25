<?php

namespace App\Livewire\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;

/**
 * After paying, Paddle sends the player back before its webhook activates the subscription,
 * so the page keeps looking for it for a minute.
 */
trait AwaitsSubscription
{
    /**
     * How many times, two seconds apart, to look for the subscription.
     */
    private static int $subscriptionChecksAllowed = 30;

    /** @var 'activating'|'active'|'delayed'|null */
    #[Locked]
    public ?string $subscriptionState = null;

    #[Locked]
    public int $subscriptionChecks = 0;

    /**
     * Start looking for the subscription when Paddle sent the player back here.
     */
    protected function awaitSubscriptionAfterCheckout(): void
    {
        $user = Auth::user();

        if (request()->boolean('subscribed') && $user instanceof User) {
            $this->subscriptionState = $user->subscribed() ? 'active' : 'activating';
        }
    }

    public function checkSubscription(): void
    {
        if ($this->subscriptionState !== 'activating') {
            return;
        }

        $user = Auth::user();

        // Reloaded, since the subscription is created by a webhook outside this request.
        if ($user instanceof User && $user->fresh()?->subscribed()) {
            $this->subscriptionState = 'active';

            return;
        }

        if (++$this->subscriptionChecks >= self::$subscriptionChecksAllowed) {
            $this->subscriptionState = 'delayed';
        }
    }
}
