<?php

namespace App\Livewire;

use App\Actions\StartSubscriptionCheckout;
use App\Livewire\Concerns\AwaitsSubscription;
use App\Models\User;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Laravel\Paddle\Subscription;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The player's account: the subscription that lets them save playlists, and how to manage it.
 */
class Account extends Component
{
    use AwaitsSubscription;

    /** @var 'checkout'|'cancel'|'resume'|'payment_method'|null */
    #[Locked]
    public ?string $errorKey = null;

    public function mount(): void
    {
        $this->awaitSubscriptionAfterCheckout();
    }

    public function subscribe(StartSubscriptionCheckout $checkout): void
    {
        if ($this->user()->subscribed()) {
            return;
        }

        $this->errorKey = null;

        try {
            $options = $checkout($this->user(), route('account', ['subscribed' => 1]));
        } catch (Exception $exception) {
            report($exception);
            $this->errorKey = 'checkout';

            return;
        }

        $this->dispatch('paddle-checkout', options: $options);
    }

    /**
     * Stop renewing: the subscription, and saving, keep working until the paid month ends.
     */
    public function cancel(): void
    {
        $subscription = $this->subscription();

        if ($subscription === null || ! $subscription->active() || $subscription->onGracePeriod()) {
            return;
        }

        $this->manage('cancel', fn () => $subscription->cancel());
    }

    /**
     * Take back a cancelation before the paid month ends.
     */
    public function resume(): void
    {
        $subscription = $this->subscription();

        if ($subscription === null || ! $subscription->onGracePeriod()) {
            return;
        }

        $this->manage('resume', fn () => $subscription->stopCancelation());
    }

    /**
     * Send the player to Paddle's page for changing the card on file.
     */
    public function updatePaymentMethod(): void
    {
        $subscription = $this->subscription();

        if ($subscription === null) {
            return;
        }

        $this->manage('payment_method', fn () => $this->redirect($subscription->paymentMethodUpdateUrl()));
    }

    public function render(): View
    {
        return view('livewire.account', [
            'user' => $this->user(),
            'subscription' => $this->subscription(),
        ])->title(__('moodler.account.title').' · '.config('app.name'));
    }

    /**
     * Run a call to Paddle, turning a failure into a message for the player.
     *
     * @param  'cancel'|'resume'|'payment_method'  $action
     */
    private function manage(string $action, callable $call): void
    {
        $this->errorKey = null;

        try {
            $call();
        } catch (Exception $exception) {
            report($exception);
            $this->errorKey = $action;
        }
    }

    private function user(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    private function subscription(): ?Subscription
    {
        $subscription = $this->user()->subscription();

        return $subscription?->valid() ? $subscription : null;
    }
}
