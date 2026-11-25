{{-- The moodler wordmark, linking home, with the account menu. Extra controls go in the slot. --}}
<header class="mb-8 flex flex-wrap items-center justify-between gap-x-6 gap-y-3">
    <div class="flex min-w-0 items-baseline gap-3">
        <a href="{{ route('home') }}" class="font-display text-3xl font-semibold tracking-tight text-white">moodler</a>
        <p class="hidden text-sm text-white/50 sm:block">{{ __('moodler.tagline') }}</p>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        {{ $slot }}
        <x-account-nav />
    </div>
</header>
