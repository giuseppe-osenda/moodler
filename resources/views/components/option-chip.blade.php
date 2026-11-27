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
    {{ $attributes->class('rounded-full border border-ink/10 bg-white/70 px-3 py-1.5 text-sm text-ink/75 transition hover:border-ink/30 hover:text-ink focus-visible:ring-2 focus-visible:ring-lilac-400 focus-visible:outline-none aria-pressed:border-(--tint-edge) aria-pressed:bg-(--tint) aria-pressed:text-(--tint-deep) aria-pressed:font-semibold disabled:cursor-not-allowed disabled:opacity-35 disabled:hover:border-ink/10 disabled:hover:text-ink/75') }}
>{{ $option['label'] }}</button>
