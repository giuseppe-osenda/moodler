<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:py-12">
    <x-page-header />

    <main class="mx-auto max-w-3xl">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="font-display text-3xl font-medium text-white">{{ __('moodler.library.title') }}</h1>
                <p class="mt-1 text-sm text-white/50">{{ __('moodler.library.subtitle') }}</p>
            </div>
            <a href="{{ route('home') }}" class="rounded-full bg-ember-400 px-4 py-2 text-sm font-semibold text-ink-950 transition hover:bg-ember-300">{{ __('moodler.library.new') }}</a>
        </div>

        @unless ($canOpen)
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-amber-300/20 bg-amber-300/10 px-4 py-3 text-sm text-amber-100" role="status">
                <span>{{ __('moodler.library.subscription_needed') }}</span>
                <a href="{{ route('account') }}" wire:navigate class="rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-white hover:bg-white/20">{{ __('moodler.library.subscribe') }}</a>
            </div>
        @endunless

        @forelse ($this->playlists as $playlist)
            <article wire:key="playlist-{{ $playlist->id }}" class="mb-3 flex items-center gap-4 rounded-2xl border border-white/10 bg-ink-900/80 p-3 pr-4 backdrop-blur">
                <div class="grid size-16 shrink-0 grid-cols-2 overflow-hidden rounded-xl bg-white/5">
                    @foreach (collect($playlist->tracks)->pluck('imageUrl')->filter()->unique()->take(4) as $image)
                        <img src="{{ $image }}" alt="" class="size-8 object-cover" loading="lazy">
                    @endforeach
                </div>

                <div class="min-w-0 flex-1">
                    @if ($canOpen)
                        <a href="{{ route('playlists.show', $playlist) }}" class="block truncate font-medium text-white hover:text-ember-300">{{ $playlist->name }}</a>
                    @else
                        <p class="truncate font-medium text-white/70">{{ $playlist->name }}</p>
                    @endif
                    <p class="truncate text-xs text-white/45">
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
                    class="shrink-0 rounded-full px-3 py-1.5 text-xs text-white/45 transition hover:bg-white/10 hover:text-red-200"
                >{{ __('moodler.library.delete') }}</button>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-white/15 px-6 py-12 text-center">
                <p class="text-white/70">{{ __('moodler.library.empty') }}</p>
                <a href="{{ route('home') }}" class="mt-4 inline-block text-sm text-ember-300 underline-offset-4 hover:underline">{{ __('moodler.library.new') }} →</a>
            </div>
        @endforelse
    </main>
</div>
