@props(['stacked' => false])

{{-- Sign-in link for guests; playlists, account and sign-out for players. Stacked is the phone menu's full-width list. --}}
@php
    $link = $stacked
        ? 'block rounded-2xl px-4 py-3 text-base text-ink/80 transition hover:bg-ink/5 hover:text-ink'
        : 'rounded-full px-3 py-1.5 text-ink/70 transition hover:bg-ink/5 hover:text-ink';
@endphp
<nav @class(['text-sm', 'flex items-center gap-1' => ! $stacked, 'flex flex-col' => $stacked]) aria-label="{{ __('moodler.auth.menu') }}">
    @auth
        <a href="{{ route('playlists.index') }}" wire:navigate class="{{ $link }}">{{ __('moodler.library.title') }}</a>
        <a href="{{ route('account') }}" wire:navigate class="{{ $link }}">{{ __('moodler.account.title') }}</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" @class([$link, 'w-full text-left' => $stacked, 'text-ink/55' => ! $stacked])>{{ __('moodler.auth.logout') }}</button>
        </form>
    @else
        <a href="{{ route('login') }}" @class([
            'rounded-full bg-ink font-semibold text-white transition hover:bg-ink/80',
            'px-4 py-2' => ! $stacked,
            'block px-5 py-3 text-center text-base' => $stacked,
        ])>{{ __('moodler.auth.login') }}</a>
    @endauth
</nav>
