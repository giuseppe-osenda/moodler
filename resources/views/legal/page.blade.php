@php
    $operator = config('legal.operator') ?? config('app.name');
    $email = config('legal.email');
    $placeholders = [
        ':operator' => e($operator),
        ':email' => $email ? '['.e($email).'](mailto:'.e($email).')' : '—',
        ':price' => e(__('moodler.checkout.price')),
    ];
    $markdown = fn (string $text): string => Str::markdown(strtr($text, $placeholders), ['html_input' => 'escape', 'allow_unsafe_links' => false]);
@endphp

{{-- The privacy policy or the terms: their text lives in lang/{locale}/legal.php. --}}
<x-layouts::app :title="__('legal.'.$page.'.title').' · '.config('app.name')">
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:py-12">
        <x-page-header />

        <main class="mx-auto max-w-3xl rounded-3xl border border-ink/10 bg-white/65 p-6 backdrop-blur sm:p-10">
            <h1 class="font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl">{{ __('legal.'.$page.'.title') }}</h1>
            <p class="mt-2 text-sm text-ink/50">{{ __('legal.updated', ['date' => \Illuminate\Support\Carbon::parse(config('legal.updated_at'))->translatedFormat('j F Y')]) }}</p>

            <div class="legal-text mt-6">
                {!! $markdown(__('legal.'.$page.'.intro')) !!}

                @foreach (__('legal.'.$page.'.sections') as $section)
                    <h2>{{ $section['heading'] }}</h2>
                    @foreach ($section['blocks'] as $block)
                        {!! $markdown($block) !!}
                    @endforeach
                @endforeach
            </div>
        </main>
    </div>
</x-layouts::app>
