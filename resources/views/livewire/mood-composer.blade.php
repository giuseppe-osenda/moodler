<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:py-12">
    <header @class(['mb-8 flex items-baseline gap-3', 'justify-center' => ! $this->hasList])>
        <h1 class="font-display text-3xl font-semibold tracking-tight text-white">moodler</h1>
        <p class="text-sm text-white/50">music for the moment you're in</p>
    </header>

    <div @class([
        'grid items-start gap-8 lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]' => $this->hasList,
        'mx-auto max-w-xl' => ! $this->hasList,
    ])>
        {{-- Left: the game board --}}
        <section class="rounded-3xl border border-white/10 bg-ink-900/80 p-5 shadow-2xl shadow-black/30 backdrop-blur sm:p-7">
            <nav class="mb-6 grid grid-cols-3 gap-2" aria-label="Steps">
                @foreach ([1 => 'Moment', 2 => 'Atmosphere', 3 => 'Fine-tune'] as $number => $label)
                    @php($reachable = $number === 1 || $this->hasProfile())
                    <button
                        type="button"
                        wire:click="goToStep({{ $number }})"
                        @disabled(! $reachable)
                        @class([
                            'group flex items-center gap-2 rounded-2xl px-3 py-2 text-left text-sm transition',
                            'bg-ember-400 text-ink-950' => $step === $number,
                            'bg-white/5 text-white/80 hover:bg-white/10' => $step !== $number && $reachable,
                            'cursor-not-allowed bg-white/[0.02] text-white/25' => ! $reachable,
                        ])
                        @if ($step === $number) aria-current="step" @endif
                    >
                        <span @class([
                            'grid size-6 shrink-0 place-items-center rounded-full text-xs font-semibold',
                            'bg-ink-950 text-ember-300' => $step === $number,
                            'bg-white/10' => $step !== $number,
                        ])>{{ $number }}</span>
                        <span class="truncate font-medium">{{ $label }}</span>
                    </button>
                @endforeach
            </nav>

            @if ($errorMessage)
                <div class="mb-5 rounded-2xl border border-red-400/30 bg-red-500/10 px-4 py-3 text-sm text-red-200" role="alert">
                    {{ $errorMessage }}
                </div>
            @endif

            @if ($step === 1)
                <form wire:submit="interpret" class="space-y-4" wire:key="step-phrase">
                    <div>
                        <label for="phrase" class="font-display text-2xl font-medium text-white">What's the moment?</label>
                        <p class="mt-1 text-sm text-white/50">A situation, a feeling, a memory. Moodler turns it into music.</p>
                    </div>
                    <textarea
                        id="phrase"
                        wire:model="phrase"
                        rows="3"
                        maxlength="280"
                        placeholder="e.g. sunset drive with my girlfriend after a long week"
                        class="w-full resize-none rounded-2xl border border-white/10 bg-ink-950/60 px-4 py-3 text-lg text-white placeholder:text-white/30 focus:border-ember-400 focus:ring-2 focus:ring-ember-400/30 focus:outline-none"
                    ></textarea>
                    @error('phrase') <p class="text-sm text-red-300">{{ $message }}</p> @enderror

                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-ember-400 px-5 py-3 font-semibold text-ink-950 transition hover:bg-ember-300 disabled:opacity-60" wire:loading.attr="disabled" wire:target="interpret">
                        <span wire:loading.remove wire:target="interpret">{{ $this->hasProfile() ? 'Read my mood again' : 'Read my mood' }}</span>
                        <span wire:loading wire:target="interpret">Reading your mood…</span>
                    </button>
                    @if ($this->hasProfile())
                        <p class="text-center text-xs text-white/40">Reading it again replaces the atmosphere you picked.</p>
                    @endif
                </form>
            @elseif ($step === 2)
                <div class="space-y-6" wire:key="step-atmosphere">
                    <div>
                        <h2 class="font-display text-2xl font-medium text-white">Here's what we heard</h2>
                        <p class="mt-1 text-sm text-white/50">Keep it, or tap anything to change the vibe.</p>
                    </div>

                    @foreach ($this->atmosphereGroups as $field => $group)
                        <fieldset wire:key="group-{{ $field }}">
                            <legend class="mb-2 text-xs font-semibold tracking-widest text-white/40 uppercase">{{ $group['label'] }}</legend>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($group['options'] as $option)
                                    <label wire:key="{{ $field }}-{{ $option }}" class="cursor-pointer">
                                        <input type="radio" class="peer sr-only" wire:model.live="{{ $field }}" value="{{ $option }}">
                                        <span class="inline-block rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-sm text-white/75 transition peer-checked:border-ember-400 peer-checked:bg-ember-400 peer-checked:text-ink-950 peer-focus-visible:ring-2 peer-focus-visible:ring-ember-300 hover:bg-white/10">{{ $option }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach

                    @unless ($this->hasList)
                        <button type="button" wire:click="goToStep(3)" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-white/10 px-5 py-3 font-semibold text-white transition hover:bg-white/15">
                            Fine-tune <span aria-hidden="true">→</span>
                        </button>
                    @endunless
                </div>
            @else
                <div class="space-y-6" wire:key="step-tuning">
                    <div>
                        <h2 class="font-display text-2xl font-medium text-white">Fine-tune the mix</h2>
                        <p class="mt-1 text-sm text-white/50">Slide the knobs, then let moodler pick the songs.</p>
                    </div>

                    @foreach ($this->tuningKnobs as $field => $knob)
                        <div wire:key="knob-{{ $field }}">
                            <div class="mb-2 flex items-baseline justify-between">
                                <label for="knob-{{ $field }}" class="text-sm font-medium text-white">{{ $knob['label'] }}</label>
                                <span class="text-xs tabular-nums text-white/40">{{ $this->{$field} }}</span>
                            </div>
                            <input id="knob-{{ $field }}" type="range" min="0" max="100" step="5" class="knob" wire:model.live.debounce.250ms="{{ $field }}">
                            <div class="mt-1 flex justify-between text-xs text-white/40">
                                <span>{{ $knob['low'] }}</span>
                                <span>{{ $knob['high'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Action bar --}}
            @if ($this->hasProfile() && ($step > 1 || $this->hasList))
                <div class="mt-8 space-y-3 border-t border-white/10 pt-5">
                    @if ($this->hasPendingChanges)
                        <p class="flex items-center gap-2 text-xs text-ember-300" wire:key="pending-changes">
                            <span class="size-1.5 animate-pulse rounded-full bg-ember-400"></span>
                            New settings — remix to hear them.
                        </p>
                    @endif

                    @if ($this->hasList || $step === 3)
                        <button type="button" wire:click="compose" wire:loading.attr="disabled" wire:target="compose" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-ember-400 to-dusk-400 px-5 py-3.5 font-semibold text-ink-950 shadow-lg shadow-ember-500/20 transition hover:brightness-110 disabled:opacity-60">
                            <span wire:loading.remove wire:target="compose">{{ $this->hasList ? 'Remix' : 'Create my list' }}</span>
                            <span wire:loading wire:target="compose">Mixing your songs…</span>
                        </button>
                    @endif

                    <button type="button" wire:click="restart" @if ($this->hasList) wire:confirm="Start over from scratch? Your current list will be cleared." @endif class="w-full rounded-2xl px-5 py-2 text-sm text-white/50 transition hover:bg-white/5 hover:text-white/80">
                        Start over
                    </button>
                </div>
            @endif
        </section>

        {{-- Right: the player --}}
        @if ($this->hasList)
            <section
                class="relative animate-rise-in rounded-3xl border border-white/10 bg-ink-900/80 p-5 shadow-2xl shadow-black/30 backdrop-blur sm:p-7 lg:sticky lg:top-8"
                x-data="spotifyPlayer(@js(array_column($tracks, 'uri')))"
                x-on:tracks-updated.window="load($event.detail.uris)"
                wire:key="player"
            >
                <div class="mb-4 flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold tracking-widest text-white/40 uppercase">Your list</p>
                        <p class="mt-1 truncate font-display text-xl text-white">“{{ $phrase }}”</p>
                    </div>
                    <button
                        type="button"
                        class="shrink-0 rounded-full border border-white/10 px-3 py-1.5 text-xs text-white/70 transition hover:bg-white/10"
                        x-on:click="copy(@js(collect($tracks)->map(fn ($track) => $track['title'].' — '.$track['artists'])->implode("\n")))"
                        x-text="copied ? 'Copied!' : 'Copy list'"
                    >Copy list</button>
                </div>

                <div wire:ignore class="min-h-[152px] overflow-hidden rounded-2xl bg-ink-950/60">
                    <div x-ref="embed"></div>
                </div>

                <ol class="mt-4 max-h-[60vh] space-y-1 overflow-y-auto pr-1">
                    @foreach ($tracks as $index => $track)
                        <li wire:key="track-{{ $track['id'] }}">
                            <div
                                role="button"
                                tabindex="0"
                                x-on:click="play({{ $index }})"
                                x-on:keydown.enter.prevent="play({{ $index }})"
                                class="group flex w-full items-center gap-3 rounded-xl px-2 py-2 text-left transition hover:bg-white/5 focus-visible:ring-2 focus-visible:ring-ember-300 focus-visible:outline-none"
                                x-bind:class="current === {{ $index }} ? 'bg-white/10' : ''"
                            >
                                <span class="w-5 shrink-0 text-right text-xs tabular-nums text-white/35" x-bind:class="current === {{ $index }} ? 'text-ember-300!' : ''">{{ $index + 1 }}</span>
                                @if ($track['imageUrl'])
                                    <img src="{{ $track['imageUrl'] }}" alt="" class="size-10 shrink-0 rounded-md object-cover" loading="lazy">
                                @else
                                    <span class="size-10 shrink-0 rounded-md bg-white/10"></span>
                                @endif
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium text-white" x-bind:class="current === {{ $index }} ? 'text-ember-300!' : ''">{{ $track['title'] }}</span>
                                    <span class="block truncate text-xs text-white/50">{{ $track['artists'] }}</span>
                                </span>
                                <span class="shrink-0 text-xs tabular-nums text-white/35">{{ sprintf('%d:%02d', intdiv(intdiv($track['durationMs'], 1000), 60), intdiv($track['durationMs'], 1000) % 60) }}</span>
                                <a href="{{ $track['url'] }}" target="_blank" rel="noopener" x-on:click.stop class="shrink-0 rounded-md px-1.5 py-1 text-xs text-white/40 opacity-0 transition group-hover:opacity-100 hover:text-white focus-visible:opacity-100" title="Open in Spotify">↗</a>
                            </div>
                        </li>
                    @endforeach
                </ol>

                <p class="mt-4 text-center text-xs text-white/35">Log in to Spotify in this browser to hear full tracks instead of previews.</p>

                <div wire:loading.flex wire:target="compose" class="absolute inset-0 z-10 flex-col items-center justify-center gap-3 rounded-3xl bg-ink-950/80 backdrop-blur-sm">
                    <span class="size-8 animate-spin rounded-full border-2 border-white/15 border-t-ember-400"></span>
                    <span class="text-sm text-white/70">Mixing a new list…</span>
                </div>
            </section>
        @endif
    </div>
</div>
