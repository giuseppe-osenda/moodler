<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-ink-950">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles

        {{-- Paddle's checkout is only offered to signed-in players. --}}
        @if (auth()->check() && config('cashier.client_side_token'))
            @paddleJS
        @endif
    </head>
    <body class="moodler-backdrop min-h-screen font-sans text-white/90 antialiased">
        {{ $slot }}

        @livewireScripts
    </body>
</html>
