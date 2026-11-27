@props([
    'locales',
    'current',
    'variant' => 'dropdown',
])

{{--
    The language switch: a translate icon that opens the list of languages. Picking one sets the component's locale.
    The list variant lays the languages out inline, for the phone menu in the page header.
--}}
@if ($variant === 'list')
    <div role="group" aria-label="{{ __('moodler.language') }}">
        <p class="flex items-center gap-2 px-4 pt-1 pb-2 text-xs font-semibold tracking-wide text-ink/50 uppercase">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m10.5 21 5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802" />
            </svg>
            {{ __('moodler.language') }}
        </p>
        <div class="flex flex-wrap gap-2 px-3 pb-1">
            @foreach ($locales as $code => $name)
                <button
                    type="button"
                    aria-pressed="{{ $code === $current ? 'true' : 'false' }}"
                    lang="{{ $code }}"
                    x-on:click="menuOpen = false; $wire.set('locale', @js($code))"
                    class="rounded-full border border-white/80 bg-white/45 px-3.5 py-2 text-sm text-ink/75 transition hover:border-ink/30 hover:text-ink aria-pressed:border-lilac-400 aria-pressed:bg-lilac-200 aria-pressed:font-semibold aria-pressed:text-lilac-900"
                >{{ $name }}</button>
            @endforeach
        </div>
    </div>
@else
    <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape.window="open = false">
        <button
            type="button"
            x-on:click="open = ! open"
            x-bind:aria-expanded="open"
            aria-haspopup="menu"
            aria-label="{{ __('moodler.language') }}"
            class="grid size-10 place-items-center rounded-full border border-ink/10 bg-white/70 text-ink/75 transition hover:border-ink/25 hover:text-ink focus-visible:ring-2 focus-visible:ring-lilac-400 focus-visible:outline-none"
        >
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m10.5 21 5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802" />
            </svg>
        </button>

        <div
            x-show="open"
            x-cloak
            x-transition.origin.top.right
            role="menu"
            aria-label="{{ __('moodler.language') }}"
            class="absolute right-0 z-40 mt-2 w-44 rounded-2xl border border-ink/10 bg-white/95 p-1.5 backdrop-blur"
        >
            @foreach ($locales as $code => $name)
                <button
                    type="button"
                    role="menuitemradio"
                    aria-checked="{{ $code === $current ? 'true' : 'false' }}"
                    lang="{{ $code }}"
                    x-on:click="open = false; $wire.set('locale', @js($code))"
                    class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2 text-left text-sm text-ink/75 transition hover:bg-ink/5 hover:text-ink aria-checked:font-semibold aria-checked:text-ink"
                >
                    {{ $name }}
                    @if ($code === $current)
                        <svg class="size-4 text-lilac-700" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.58l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd" /></svg>
                    @endif
                </button>
            @endforeach
        </div>
    </div>
@endif
