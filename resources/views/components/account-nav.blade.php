{{-- Sign-in link for guests; playlists, account and sign-out for players. --}}
<nav class="flex items-center gap-1 text-sm" aria-label="{{ __('moodler.auth.menu') }}">
    @auth
        <a href="{{ route('playlists.index') }}" wire:navigate class="rounded-full px-3 py-1.5 text-white/70 transition hover:bg-white/10 hover:text-white">{{ __('moodler.library.title') }}</a>
        <a href="{{ route('account') }}" wire:navigate class="rounded-full px-3 py-1.5 text-white/70 transition hover:bg-white/10 hover:text-white">{{ __('moodler.account.title') }}</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="rounded-full px-3 py-1.5 text-white/50 transition hover:bg-white/10 hover:text-white">{{ __('moodler.auth.logout') }}</button>
        </form>
    @else
        <a href="{{ route('login') }}" class="rounded-full border border-white/15 px-3 py-1.5 text-white/85 transition hover:bg-white/10">{{ __('moodler.auth.login') }}</a>
    @endauth
</nav>
