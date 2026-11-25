@props(['state'])

{{-- Where a just-paid subscription stands. While Paddle's webhook has not arrived yet, it keeps checking. --}}
@if ($state)
    <div
        @if ($state === 'activating') wire:poll.2s="checkSubscription" @endif
        @class([
            'mx-auto mb-6 flex max-w-xl items-center gap-3 rounded-2xl border px-4 py-3 text-sm',
            'border-white/10 bg-white/5 text-white/75' => $state === 'activating',
            'border-emerald-300/20 bg-emerald-300/10 text-emerald-100' => $state === 'active',
            'border-amber-300/20 bg-amber-300/10 text-amber-100' => $state === 'delayed',
        ])
        role="status"
        wire:key="subscription-{{ $state }}"
    >
        @if ($state === 'activating')
            <span class="size-4 shrink-0 animate-spin rounded-full border-2 border-white/15 border-t-ember-400"></span>
        @endif
        {{ __("moodler.checkout.{$state}") }}
    </div>
@endif
