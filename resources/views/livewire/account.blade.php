<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:py-12">
    <x-page-header />
    <x-subscription-banner :state="$subscriptionState" />

    <main class="mx-auto max-w-xl space-y-6">
        <h1 class="font-display text-3xl font-bold tracking-tight text-ink">{{ __('moodler.account.title') }}</h1>

        @if (session('status'))
            <div class="rounded-2xl border border-amber-300/70 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status">{{ session('status') }}</div>
        @endif

        <section class="rounded-3xl border border-ink/10 bg-white/65 p-6 backdrop-blur sm:p-7">
            <h2 class="text-xs font-semibold tracking-widest text-ink/50 uppercase">{{ __('moodler.account.profile') }}</h2>
            <p class="mt-3 text-lg text-ink">{{ $user->name }}</p>
            <p class="text-sm text-ink/60">{{ $user->email }}</p>
        </section>

        <section class="rounded-3xl border border-ink/10 bg-white/65 p-6 backdrop-blur sm:p-7" wire:key="subscription-card">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xs font-semibold tracking-widest text-ink/50 uppercase">{{ __('moodler.account.subscription') }}</h2>
                @if ($subscription)
                    <span @class([
                        'rounded-full px-2.5 py-1 text-xs font-medium',
                        'bg-emerald-100 text-emerald-800' => ! $subscription->onGracePeriod() && ! $subscription->pastDue(),
                        'bg-amber-100 text-amber-800' => $subscription->onGracePeriod() || $subscription->pastDue(),
                    ])>
                        {{ $subscription->onGracePeriod() ? __('moodler.account.status.canceled') : ($subscription->pastDue() ? __('moodler.account.status.past_due') : __('moodler.account.status.active')) }}
                    </span>
                @endif
            </div>

            @if (! $subscription)
                <p class="mt-3 text-ink/75">{{ __('moodler.account.pitch') }}</p>
                <button type="button" wire:click="subscribe" wire:loading.attr="disabled" wire:target="subscribe" class="mt-4 inline-flex w-full items-center justify-center rounded-2xl bg-ink px-5 py-3 font-semibold text-white transition hover:bg-ink/85 disabled:opacity-60">
                    <span wire:loading.remove wire:target="subscribe">{{ __('moodler.save.subscribe', ['price' => __('moodler.checkout.price')]) }}</span>
                    <span wire:loading wire:target="subscribe">{{ __('moodler.checkout.opening') }}</span>
                </button>
            @elseif ($subscription->onGracePeriod())
                <p class="mt-3 text-ink/75">{{ __('moodler.account.ends_on', ['date' => $subscription->ends_at->locale(app()->getLocale())->isoFormat('LL')]) }}</p>
                <button type="button" wire:click="resume" class="mt-4 inline-flex w-full items-center justify-center rounded-2xl bg-ink px-5 py-3 font-semibold text-white transition hover:bg-ink/85">{{ __('moodler.account.resume') }}</button>
            @else
                <p class="mt-3 text-ink/75">{{ $subscription->pastDue() ? __('moodler.account.past_due') : __('moodler.account.renews', ['price' => __('moodler.checkout.price')]) }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="button" wire:click="updatePaymentMethod" class="flex-1 rounded-2xl border border-ink/10 bg-white/90 px-4 py-2.5 text-sm font-semibold text-ink transition hover:bg-white">{{ __('moodler.account.payment_method') }}</button>
                    <button type="button" wire:click="cancel" wire:confirm="{{ __('moodler.account.cancel_confirm') }}" class="flex-1 rounded-2xl px-4 py-2.5 text-sm text-ink/60 transition hover:bg-white/70 hover:text-ink">{{ __('moodler.account.cancel') }}</button>
                </div>
            @endif

            @if ($errorKey)
                <p class="mt-4 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700" role="alert">{{ __("moodler.account.errors.{$errorKey}") }}</p>
            @endif
        </section>

        <a href="{{ route('playlists.index') }}" wire:navigate class="block text-center text-sm text-lilac-700 underline-offset-4 hover:underline">{{ __('moodler.account.to_playlists') }} →</a>
    </main>
</div>
