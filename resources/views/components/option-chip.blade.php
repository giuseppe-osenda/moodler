@props([
    'field',
    'option',
    'selected' => false,
    'disabled' => false,
])

{{-- A toggleable mood or genre. Its description, when known, shows as a tooltip on hover or focus. --}}
<button
    type="button"
    wire:click="toggleOption('{{ $field }}', {{ Js::from($option['value']) }})"
    aria-pressed="{{ $selected ? 'true' : 'false' }}"
    data-value="{{ $option['value'] }}"
    data-search="{{ $option['value'] }} {{ $option['label'] }}"
    data-selected="{{ $selected ? 'true' : 'false' }}"
    @if ($option['builtIn'] ?? false)
        data-builtin
    @endif
    @if (filled($option['description'] ?? null))
        data-tooltip="{{ $option['description'] }}"
        aria-description="{{ $option['description'] }}"
    @endif
    @disabled($disabled)
    {{ $attributes->class('rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-sm text-white/75 transition hover:bg-white/10 focus-visible:ring-2 focus-visible:ring-ember-300 focus-visible:outline-none aria-pressed:border-ember-400 aria-pressed:bg-ember-400 aria-pressed:text-ink-950 disabled:cursor-not-allowed disabled:opacity-35 disabled:hover:bg-white/5') }}
>{{ $option['label'] }}</button>
