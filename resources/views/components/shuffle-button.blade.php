@props(['label'])

{{-- Draws random choices. The die spins while the shuffle animation plays. --}}
<button
    type="button"
    aria-label="{{ $label }}"
    data-tooltip="{{ $label }}"
    x-bind:disabled="shuffling"
    {{ $attributes->class('group inline-flex size-9 shrink-0 items-center justify-center rounded-full border border-ink/10 bg-white/70 text-ink/80 transition hover:border-ink/30 hover:bg-white hover:text-lilac-700 focus-visible:ring-2 focus-visible:ring-lilac-400 focus-visible:outline-none active:scale-90 disabled:cursor-wait') }}
>
    <svg class="size-5 transition-transform duration-500 ease-out group-hover:rotate-[20deg]" x-bind:class="shuffling && 'dice-rolling'" viewBox="0 0 24 24" aria-hidden="true">
        {{-- A die seen from a corner: lit top, shaded sides, pips one, two and three. --}}
        <path d="M12 2.5 20.5 7.25v9.5L12 21.5l-8.5-4.75v-9.5Z" fill="currentColor" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
        <path d="M3.5 7.25 12 12v9.5l-8.5-4.75Z" fill="#000" fill-opacity="0.32" />
        <path d="M12 12l8.5-4.75v9.5L12 21.5Z" fill="#000" fill-opacity="0.14" />
        <path d="M3.5 7.25 12 12l8.5-4.75M12 12v9.5" fill="none" stroke="#000" stroke-opacity="0.18" stroke-width="0.6" stroke-linejoin="round" />
        <g fill="#fff">
            <ellipse cx="12" cy="7.25" rx="1.75" ry="1" />
            <ellipse cx="6" cy="11.2" rx="0.85" ry="1.4" transform="rotate(-30 6 11.2)" />
            <ellipse cx="9.5" cy="17.4" rx="0.85" ry="1.4" transform="rotate(-30 9.5 17.4)" />
            <ellipse cx="13.75" cy="18.5" rx="0.85" ry="1.4" transform="rotate(30 13.75 18.5)" />
            <ellipse cx="16.25" cy="14.4" rx="0.85" ry="1.4" transform="rotate(30 16.25 14.4)" />
            <ellipse cx="18.75" cy="10.25" rx="0.85" ry="1.4" transform="rotate(30 18.75 10.25)" />
        </g>
    </svg>
</button>
