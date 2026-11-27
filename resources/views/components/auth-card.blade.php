@props([
    'title',
    'subtitle' => null,
])

{{-- A narrow card for the sign-in, sign-up and password screens. --}}
<x-layouts::app :title="$title.' · '.config('app.name')">
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:py-12">
        <x-page-header />

        <main class="mx-auto max-w-md rounded-3xl border border-ink/10 bg-white/65 p-6 backdrop-blur sm:p-8">
            <h1 class="font-display text-2xl font-bold tracking-tight text-ink">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-1 text-sm text-ink/60">{{ $subtitle }}</p>
            @endif

            @if (session('status'))
                <div class="mt-5 rounded-2xl border border-emerald-300/70 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">
                    {{ session('status') === 'verification-link-sent' ? __('moodler.auth.verify.sent') : session('status') }}
                </div>
            @endif

            <div class="mt-6">
                {{ $slot }}
            </div>
        </main>
    </div>
</x-layouts::app>
