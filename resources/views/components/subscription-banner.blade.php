@props(['state'])

{{-- Where a just-paid subscription stands. While Paddle's webhook has not arrived yet, it keeps checking. --}}
@if ($state)
    <div
        @if ($state === 'activating') wire:poll.2s="checkSubscription" @endif
        @class([
            'mx-auto mb-6 flex max-w-xl items-center gap-3 rounded-2xl border px-4 py-3 text-sm',
            'border-ink/10 bg-white/70 text-ink/75' => $state === 'activating',
            'border-emerald-300/70 bg-emerald-50 text-emerald-900' => $state === 'active',
            'border-amber-300/70 bg-amber-50 text-amber-900' => $state === 'delayed',
        ])
        role="status"
        wire:key="subscription-{{ $state }}"
    >
        @if ($state === 'activating')
            <span class="size-4 shrink-0 animate-spin rounded-full border-2 border-ink/10 border-t-lilac-500"></span>
        @endif
        {{ __("moodler.checkout.{$state}") }}
    </div>
@endif
