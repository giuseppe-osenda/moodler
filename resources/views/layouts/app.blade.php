<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-canvas">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name') }}</title>
        <meta name="description" content="{{ __('moodler.tagline') }}">
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles

        {{-- Paddle's checkout is only offered to signed-in players. --}}
        @if (auth()->check() && config('cashier.client_side_token'))
            @paddleJS
        @endif
    </head>
    <body class="moodler-backdrop min-h-screen font-sans text-ink/90 antialiased">
        {{ $slot }}

        <footer class="mx-auto max-w-6xl px-4 pb-8 text-center text-xs text-ink/50 sm:px-6">
            <a href="{{ route('privacy') }}" class="rounded-full px-2 py-1 transition hover:text-ink">{{ __('legal.nav.privacy') }}</a>
            <span aria-hidden="true">·</span>
            <a href="{{ route('terms') }}" class="rounded-full px-2 py-1 transition hover:text-ink">{{ __('legal.nav.terms') }}</a>
        </footer>

        @livewireScripts
    </body>
</html>
