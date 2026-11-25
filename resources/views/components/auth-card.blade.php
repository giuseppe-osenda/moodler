@props([
    'title',
    'subtitle' => null,
])

{{-- A narrow card for the sign-in, sign-up and password screens. --}}
<x-layouts::app :title="$title.' · '.config('app.name')">
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:py-12">
        <x-page-header />

        <main class="mx-auto max-w-md rounded-3xl border border-white/10 bg-ink-900/80 p-6 shadow-2xl shadow-black/30 backdrop-blur sm:p-8">
            <h1 class="font-display text-2xl font-medium text-white">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-1 text-sm text-white/55">{{ $subtitle }}</p>
            @endif

            @if (session('status'))
                <div class="mt-5 rounded-2xl border border-emerald-300/20 bg-emerald-300/10 px-4 py-3 text-sm text-emerald-100" role="status">
                    {{ session('status') === 'verification-link-sent' ? __('moodler.auth.verify.sent') : session('status') }}
                </div>
            @endif

            <div class="mt-6">
                {{ $slot }}
            </div>
        </main>
    </div>
</x-layouts::app>
