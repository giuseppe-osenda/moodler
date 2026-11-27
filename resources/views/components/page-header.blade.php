@props(['mobile' => null])

{{--
    The moodler logo, linking home, with the page's controls (the slot) and the account menu.
    On phones those controls move into a glass sheet that slides up from the bottom, opened by the music note button;
    the `mobile` slot holds the phone version of the page's controls. The sheet closes on a tap outside, Esc or a swipe down.
--}}
<header
    class="relative mb-8 flex items-center justify-between gap-x-6 gap-y-3"
    x-data="{ menuOpen: false, dragStart: null, dragY: 0 }"
    x-effect="document.documentElement.classList.toggle('overflow-hidden', menuOpen)"
    x-on:keydown.escape.window="menuOpen = false"
>
    <a href="{{ route('home') }}" class="group flex items-center gap-2 font-display text-3xl leading-none font-extrabold tracking-tight text-ink">
        <x-logo-mark class="h-13 w-auto" />
        moodler
    </a>

    <div class="hidden items-center gap-3 sm:flex">
        {{ $slot }}
        <x-account-nav />
    </div>

    <button
        type="button"
        class="grid size-11 place-items-center rounded-full border border-ink/10 bg-white/70 text-ink transition duration-300 hover:border-ink/25 focus-visible:ring-2 focus-visible:ring-lilac-400 focus-visible:outline-none aria-expanded:border-ink aria-expanded:bg-ink aria-expanded:text-white sm:hidden"
        x-on:click="menuOpen = ! menuOpen"
        x-bind:aria-expanded="menuOpen"
        aria-controls="mobile-menu"
        aria-label="{{ __('moodler.auth.open_menu') }}"
    >
        <svg class="size-5 transition duration-300" x-bind:class="menuOpen && '-rotate-12 scale-110'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M9 9l10.5-3m0 6.553v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 1 1-.99-3.467l2.31-.66a2.25 2.25 0 0 0 1.632-2.163Zm0 0V2.25L9 5.25v10.303m0 0v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 0 1-.99-3.467l2.31-.66A2.25 2.25 0 0 0 9 15.553Z" />
        </svg>
    </button>

    <div
        x-show="menuOpen"
        x-cloak
        x-transition.opacity.duration.300ms
        x-on:click="menuOpen = false"
        class="fixed inset-0 z-40 bg-ink/15 backdrop-blur-[2px] sm:hidden"
        aria-hidden="true"
    ></div>

    <div
        id="mobile-menu"
        x-show="menuOpen"
        x-cloak
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave-end="translate-y-full opacity-0"
        x-on:touchstart="dragStart = $event.touches[0].clientY"
        x-on:touchmove="dragY = Math.max(0, $event.touches[0].clientY - dragStart)"
        x-on:touchend="dragStart = null; if (dragY > 80) { menuOpen = false; setTimeout(() => dragY = 0, 500) } else { dragY = 0 }"
        x-bind:class="dragStart !== null && 'is-dragging'"
        x-bind:style="{ transform: dragY ? `translateY(${dragY}px)` : '' }"
        class="glass-sheet fixed inset-x-3 bottom-[max(0.75rem,env(safe-area-inset-bottom))] z-50 space-y-3 rounded-[2rem] px-3 pt-2.5 pb-3 sm:hidden"
    >
        <div class="mx-auto h-1.5 w-10 rounded-full bg-ink/20" aria-hidden="true"></div>
        @if ($mobile?->isNotEmpty())
            {{ $mobile }}
            <div class="mx-3 h-px bg-ink/10"></div>
        @endif
        <x-account-nav stacked />
    </div>
</header>
