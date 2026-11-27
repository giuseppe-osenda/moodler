<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:py-12">
    <x-page-header />

    <main class="mx-auto max-w-3xl">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="font-display text-3xl font-bold tracking-tight text-ink">{{ __('moodler.library.title') }}</h1>
                <p class="mt-1 text-sm text-ink/55">{{ __('moodler.library.subtitle') }}</p>
            </div>
            <a href="{{ route('home') }}" class="rounded-full bg-ink px-4 py-2 text-sm font-semibold text-white transition hover:bg-ink/85">{{ __('moodler.library.new') }}</a>
        </div>

        @unless ($canOpen)
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-amber-300/70 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status">
                <span>{{ __('moodler.library.subscription_needed') }}</span>
                <a href="{{ route('account') }}" wire:navigate class="rounded-full bg-white/90 px-3 py-1 text-xs font-medium text-ink hover:bg-white">{{ __('moodler.library.subscribe') }}</a>
            </div>
        @endunless

        @forelse ($this->playlists as $playlist)
            <article wire:key="playlist-{{ $playlist->id }}" class="mb-3 flex items-center gap-4 rounded-2xl border border-ink/10 bg-white/65 p-3 pr-4 backdrop-blur">
                <div class="grid size-16 shrink-0 grid-cols-2 overflow-hidden rounded-xl bg-white/70">
                    @foreach (collect($playlist->tracks)->pluck('imageUrl')->filter()->unique()->take(4) as $image)
                        <img src="{{ $image }}" alt="" class="size-8 object-cover" loading="lazy">
                    @endforeach
                </div>

                <div class="min-w-0 flex-1">
                    @if ($canOpen)
                        <a href="{{ route('playlists.show', $playlist) }}" class="block truncate font-medium text-ink hover:text-lilac-700">{{ $playlist->name }}</a>
                    @else
                        <p class="truncate font-medium text-ink/70">{{ $playlist->name }}</p>
                    @endif
                    <p class="truncate text-xs text-ink/55">
                        {{ trans_choice('moodler.library.tracks', count($playlist->tracks), ['count' => count($playlist->tracks)]) }}
                        · {{ $playlist->updated_at->locale(app()->getLocale())->diffForHumans() }}
                        @if ($playlist->name !== $playlist->phrase)
                            · “{{ $playlist->phrase }}”
                        @endif
                    </p>
                </div>

                <button
                    type="button"
                    wire:click="delete({{ $playlist->id }})"
                    wire:confirm="{{ __('moodler.library.delete_confirm', ['name' => $playlist->name]) }}"
                    class="shrink-0 rounded-full px-3 py-1.5 text-xs text-ink/55 transition hover:bg-ink/5 hover:text-red-700"
                >{{ __('moodler.library.delete') }}</button>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-ink/10 px-6 py-12 text-center">
                <p class="text-ink/70">{{ __('moodler.library.empty') }}</p>
                <a href="{{ route('home') }}" class="mt-4 inline-block text-sm text-lilac-700 underline-offset-4 hover:underline">{{ __('moodler.library.new') }} →</a>
            </div>
        @endforelse
    </main>
</div>
