<div
    class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:py-12"
    x-data="chipTooltip"
    x-on:mouseover="show($event)"
    x-on:mouseout="hide($event)"
    x-on:focusin="show($event)"
    x-on:focusout="hide($event)"
    x-on:scroll.window.passive="close()"
>
    {{-- One shared tooltip, fixed to the viewport so it is never clipped by the cards. --}}
    <div wire:ignore>
        <div
            x-ref="tooltip"
            x-show="visible"
            x-cloak
            x-text="text"
            x-bind:style="`left: ${x}px; top: ${y}px`"
            aria-hidden="true"
            class="pointer-events-none fixed z-50 max-w-64 rounded-xl bg-ink-950/95 px-3 py-2 text-xs leading-relaxed text-white/85 shadow-xl ring-1 ring-white/15 backdrop-blur"
        ></div>
    </div>

    <x-page-header>
        <label class="flex items-center gap-2 text-xs text-white/50">
            <span class="sr-only sm:not-sr-only">{{ __('moodler.language') }}</span>
            <select
                wire:model.live="locale"
                class="rounded-full border border-white/10 bg-ink-900 py-1.5 pr-8 pl-3 text-sm text-white/85 transition hover:border-white/25 focus:border-ember-400 focus:ring-2 focus:ring-ember-400/30 focus:outline-none"
            >
                @foreach ($this->supportedLocales() as $code => $name)
                    <option value="{{ $code }}" lang="{{ $code }}">{{ $name }}</option>
                @endforeach
            </select>
        </label>
    </x-page-header>

    <x-subscription-banner :state="$subscriptionState" />

    <div @class([
        'grid items-start gap-8 lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]' => $this->hasList,
        'mx-auto max-w-xl' => ! $this->hasList,
    ])>
        {{-- Left: the game board --}}
        <section class="rounded-3xl border border-white/10 bg-ink-900/80 p-5 shadow-2xl shadow-black/30 backdrop-blur sm:p-7">
            <form id="section-moment" wire:submit="interpret" class="scroll-mt-6 space-y-4" wire:key="section-moment">
                <div>
                    <p class="mb-1 text-xs font-semibold tracking-widest text-ember-300/80 uppercase">1 · {{ __('moodler.steps.moment') }}</p>
                    <label for="phrase" class="font-display text-2xl font-medium text-white">{{ __('moodler.phrase.title') }}</label>
                    <p class="mt-1 text-sm text-white/50">{{ __('moodler.phrase.subtitle') }}</p>
                </div>
                <textarea
                    id="phrase"
                    wire:model="phrase"
                    rows="3"
                    maxlength="280"
                    placeholder="{{ __('moodler.phrase.placeholder') }}"
                    class="w-full resize-none rounded-2xl border border-white/10 bg-ink-950/60 px-4 py-3 text-lg text-white placeholder:text-white/30 focus:border-ember-400 focus:ring-2 focus:ring-ember-400/30 focus:outline-none"
                ></textarea>
                @error('phrase') <p class="text-sm text-red-300">{{ $message }}</p> @enderror

                @if ($errorKey === 'interpret')
                    <div class="rounded-2xl border border-red-400/30 bg-red-500/10 px-4 py-3 text-sm text-red-200" role="alert">{{ __('moodler.errors.interpret') }}</div>
                @endif

                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-ember-400 px-5 py-3 font-semibold text-ink-950 transition hover:bg-ember-300 disabled:opacity-60" wire:loading.attr="disabled" wire:target="interpret">
                    <span wire:loading.remove wire:target="interpret">{{ $this->hasProfile() ? __('moodler.phrase.submit_again') : __('moodler.phrase.submit') }}</span>
                    <span wire:loading wire:target="interpret">{{ __('moodler.phrase.submitting') }}</span>
                </button>
                @if ($this->hasProfile())
                    <p class="text-center text-xs text-white/40">{{ __('moodler.phrase.replace_note') }}</p>
                @endif
            </form>

            @if ($this->showsAtmosphere())
                <div id="section-atmosphere" class="mt-8 animate-rise-in scroll-mt-6 space-y-6 border-t border-white/10 pt-7" wire:key="section-atmosphere">
                    <div>
                        <p class="mb-1 text-xs font-semibold tracking-widest text-ember-300/80 uppercase">2 · {{ __('moodler.steps.atmosphere') }}</p>
                        <h2 class="font-display text-2xl font-medium text-white">{{ __('moodler.atmosphere.title') }}</h2>
                        <p class="mt-1 text-sm text-white/50">{{ __('moodler.atmosphere.subtitle') }}</p>
                    </div>

                    @foreach ($this->atmosphereGroups() as $field => $group)
                        <fieldset wire:key="group-{{ $field }}" @if ($group['limit']) x-data="optionSearch(@js($field))" @endif>
                            <legend class="mb-2 text-xs font-semibold tracking-widest text-white/40 uppercase">
                                {{ $group['label'] }}
                                @if ($group['limit'])
                                    <span class="ml-1 font-normal tracking-normal normal-case text-white/30">· {{ __('moodler.atmosphere.pick_up_to', ['max' => $group['limit']]) }}</span>
                                @endif
                            </legend>

                            @if ($group['limit'])
                                <div class="relative mb-3">
                                    <svg class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-white/35" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.45 4.39l3.08 3.08a.75.75 0 1 1-1.06 1.06l-3.08-3.08A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
                                    </svg>
                                    <input
                                        type="search"
                                        x-model="query"
                                        x-on:input.debounce.500ms="lookup()"
                                        x-on:keydown.escape.prevent="clear()"
                                        maxlength="40"
                                        autocomplete="off"
                                        spellcheck="false"
                                        placeholder="{{ __("moodler.atmosphere.search.{$field}.placeholder") }}"
                                        aria-label="{{ __("moodler.atmosphere.search.{$field}.placeholder") }}"
                                        class="w-full rounded-xl border border-white/10 bg-ink-950/60 py-2 pr-9 pl-9 text-sm text-white placeholder:text-white/30 transition focus:border-ember-400 focus:ring-2 focus:ring-ember-400/30 focus:outline-none"
                                    >
                                    <span x-show="searching" x-cloak class="absolute top-1/2 right-3 size-4 -translate-y-1/2 animate-spin rounded-full border-2 border-white/15 border-t-ember-300" role="status">
                                        <span class="sr-only">{{ __('moodler.atmosphere.search.searching') }}</span>
                                    </span>
                                </div>
                            @endif

                            <div class="flex flex-wrap gap-2" @if ($group['limit']) x-ref="options" @endif>
                                @foreach ($group['options'] as $option)
                                    @if ($group['limit'])
                                        @php($isSelected = in_array($option['value'], $group['selected'], true))
                                        <x-option-chip
                                            wire:key="{{ $field }}-{{ $option['value'] }}"
                                            x-show="isVisible($el)"
                                            :field="$field"
                                            :option="$option"
                                            :selected="$isSelected"
                                            :disabled="! $isSelected && count($group['selected']) >= $group['limit']"
                                        />
                                    @else
                                        <label wire:key="{{ $field }}-{{ $option['value'] }}" class="cursor-pointer">
                                            <input type="radio" class="peer sr-only" wire:model.live="{{ $field }}" value="{{ $option['value'] }}">
                                            <span class="inline-block rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-sm text-white/75 transition peer-checked:border-ember-400 peer-checked:bg-ember-400 peer-checked:text-ink-950 peer-focus-visible:ring-2 peer-focus-visible:ring-ember-300 hover:bg-white/10">{{ $option['label'] }}</span>
                                        </label>
                                    @endif
                                @endforeach
                            </div>

                            @if ($group['limit'])
                                @php($results = $this->catalogResults($field))
                                @if ($results !== [])
                                    <div x-show="isCurrent" class="mt-3 rounded-2xl border border-dashed border-white/10 p-3">
                                        <p class="mb-2 text-xs text-white/45">{{ __("moodler.atmosphere.search.{$field}.more") }}</p>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($results as $result)
                                                <x-option-chip
                                                    wire:key="{{ $field }}-found-{{ $result['value'] }}"
                                                    :field="$field"
                                                    :option="$result"
                                                    :disabled="count($group['selected']) >= $group['limit']"
                                                />
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <div aria-live="polite">
                                    <p x-show="showEmpty" x-cloak x-text="{{ Js::from(__("moodler.atmosphere.search.{$field}.empty")) }}.replace(':query', query.trim())" class="mt-3 rounded-xl border border-amber-300/20 bg-amber-300/10 px-3 py-2 text-sm text-amber-100"></p>
                                    <p x-show="showFailed" x-cloak class="mt-3 rounded-xl border border-rose-300/20 bg-rose-300/10 px-3 py-2 text-sm text-rose-100">{{ __('moodler.atmosphere.search.failed') }}</p>
                                </div>
                            @endif
                        </fieldset>
                    @endforeach

                    @unless ($this->showsTuning())
                        <button type="button" wire:click="revealTuning" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-white/10 px-5 py-3 font-semibold text-white transition hover:bg-white/15">
                            {{ __('moodler.atmosphere.next') }} <span aria-hidden="true">↓</span>
                        </button>
                    @endunless
                </div>
            @endif

            @if ($this->showsTuning())
                <div id="section-tuning" class="mt-8 animate-rise-in scroll-mt-6 space-y-6 border-t border-white/10 pt-7" wire:key="section-tuning">
                    <div>
                        <p class="mb-1 text-xs font-semibold tracking-widest text-ember-300/80 uppercase">3 · {{ __('moodler.steps.tuning') }}</p>
                        <h2 class="font-display text-2xl font-medium text-white">{{ __('moodler.tuning.title') }}</h2>
                        <p class="mt-1 text-sm text-white/50">{{ __('moodler.tuning.subtitle') }}</p>
                    </div>

                    @foreach ($this->tuningKnobs() as $field => $knob)
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
            @if ($this->showsTuning())
                <div class="mt-8 space-y-3 border-t border-white/10 pt-5">
                    @if (in_array($errorKey, ['compose', 'no_tracks'], true))
                        <div class="rounded-2xl border border-red-400/30 bg-red-500/10 px-4 py-3 text-sm text-red-200" role="alert">{{ __("moodler.errors.{$errorKey}") }}</div>
                    @endif

                    @if ($this->hasPendingChanges)
                        <p class="flex items-center gap-2 text-xs text-ember-300" wire:key="pending-changes">
                            <span class="size-1.5 animate-pulse rounded-full bg-ember-400"></span>
                            {{ __('moodler.actions.pending') }}
                        </p>
                    @endif

                    <button type="button" wire:click="compose" wire:loading.attr="disabled" wire:target="compose" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-ember-400 to-dusk-400 px-5 py-3.5 font-semibold text-ink-950 shadow-lg shadow-ember-500/20 transition hover:brightness-110 disabled:opacity-60">
                            <span wire:loading.remove wire:target="compose">{{ $this->hasList ? __('moodler.actions.remix') : __('moodler.actions.create') }}</span>
                            <span wire:loading wire:target="compose">{{ __('moodler.actions.mixing') }}</span>
                        </button>

                    <button type="button" wire:click="restart" @if ($this->hasList) wire:confirm="{{ __('moodler.actions.restart_confirm') }}" @endif class="w-full rounded-2xl px-5 py-2 text-sm text-white/50 transition hover:bg-white/5 hover:text-white/80">
                        {{ __('moodler.actions.restart') }}
                    </button>
                </div>
            @endif
        </section>

        {{-- Right: the player --}}
        @if ($this->hasList)
            <section
                id="section-player"
                class="relative animate-rise-in scroll-mt-6 rounded-3xl border border-white/10 bg-ink-900/80 p-5 shadow-2xl shadow-black/30 backdrop-blur sm:p-7 lg:sticky lg:top-8"
                x-data="trackPlayer(@js($this->playerTracks()), @js($this->youtubeEnabled()))"
                x-on:tracks-updated.window="load($event.detail.tracks)"
                wire:key="player"
            >
                <div class="mb-4 flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold tracking-widest text-white/40 uppercase">{{ __('moodler.player.your_list') }}</p>
                        <p class="mt-1 truncate font-display text-xl text-white">{{ $this->openedPlaylistName ?? '“'.$phrase.'”' }}</p>
                    </div>
                    <button
                        type="button"
                        class="shrink-0 rounded-full border border-white/10 px-3 py-1.5 text-xs text-white/70 transition hover:bg-white/10"
                        x-on:click="copy(@js(collect($tracks)->map(fn ($track) => $track['title'].' — '.$track['artists'])->implode("\n")))"
                    >
                        <span x-show="! copied">{{ __('moodler.player.copy') }}</span>
                        <span x-show="copied" x-cloak>{{ __('moodler.player.copied') }}</span>
                    </button>
                </div>

                @if ($this->youtubeEnabled())
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <span class="text-xs text-white/45" id="playback-source-label">{{ __('moodler.player.source') }}</span>
                        <div class="inline-flex rounded-full border border-white/10 bg-ink-950/60 p-1" role="group" aria-labelledby="playback-source-label">
                            @foreach (['youtube' => 'YouTube', 'spotify' => 'Spotify'] as $source => $name)
                                <button
                                    type="button"
                                    x-on:click="setSource('{{ $source }}')"
                                    x-bind:aria-pressed="source === '{{ $source }}'"
                                    class="rounded-full px-3 py-1 text-xs font-medium text-white/60 transition hover:text-white aria-pressed:bg-white/15 aria-pressed:text-white"
                                >{{ $name }}</button>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Spotify embed --}}
                <div x-show="source === 'spotify'" class="min-h-[152px] overflow-hidden rounded-2xl bg-ink-950/60">
                    <div wire:ignore><div x-ref="spotifyEmbed"></div></div>
                </div>

                {{-- YouTube player: nothing is looked up until the listener presses play. --}}
                @if ($this->youtubeEnabled())
                    <div x-show="source === 'youtube'" x-cloak class="relative aspect-video overflow-hidden rounded-2xl bg-ink-950/60">
                        <div wire:ignore class="absolute inset-0"><div x-ref="youtubeEmbed"></div></div>

                        <button
                            type="button"
                            x-show="! youtubeStarted && ! resolving"
                            x-on:click="play(current)"
                            class="absolute inset-0 flex flex-col items-center justify-center gap-3 text-white/80 transition hover:text-white"
                        >
                            <span class="grid size-14 place-items-center rounded-full bg-ember-400 text-ink-950 shadow-lg shadow-ember-500/30">
                                <svg class="ml-1 size-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.3 2.84A1.5 1.5 0 0 0 4 4.11v11.78a1.5 1.5 0 0 0 2.3 1.27l9.34-5.89a1.5 1.5 0 0 0 0-2.54L6.3 2.84Z" /></svg>
                            </span>
                            <span class="text-sm font-medium">{{ __('moodler.player.start') }}</span>
                        </button>

                        <div x-show="resolving" x-cloak class="absolute inset-0 flex items-center justify-center gap-3 bg-ink-950/70 text-sm text-white/70">
                            <span class="size-5 animate-spin rounded-full border-2 border-white/15 border-t-ember-400"></span>
                            {{ __('moodler.player.resolving') }}
                        </div>
                    </div>
                @endif

                <div aria-live="polite">
                    <p x-show="notice === 'youtube_missing'" x-cloak class="mt-3 rounded-xl border border-amber-300/20 bg-amber-300/10 px-3 py-2 text-sm text-amber-100">{{ __('moodler.player.youtube_missing') }}</p>
                    <div x-show="notice === 'youtube_unavailable'" x-cloak class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-rose-300/20 bg-rose-300/10 px-3 py-2 text-sm text-rose-100">
                        <span>{{ __('moodler.player.youtube_unavailable') }}</span>
                        <button type="button" x-on:click="setSource('spotify')" class="rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-white hover:bg-white/20">{{ __('moodler.player.use_spotify') }}</button>
                    </div>
                    <div x-show="notice === 'youtube_limited'" x-cloak class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-amber-300/20 bg-amber-300/10 px-3 py-2 text-sm text-amber-100">
                        <span x-text="{{ Js::from(__('moodler.player.youtube_limited')) }}.replace(':minutes', retryMinutes)"></span>
                        <button type="button" x-on:click="setSource('spotify')" class="rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-white hover:bg-white/20">{{ __('moodler.player.use_spotify') }}</button>
                    </div>
                    <div x-show="notice === 'preview'" x-cloak class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-amber-300/20 bg-amber-300/10 px-3 py-2 text-sm text-amber-100">
                        <span>{{ __('moodler.player.preview') }}</span>
                        <button type="button" x-show="youtubeEnabled" x-on:click="setSource('youtube')" class="rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-white hover:bg-white/20">{{ __('moodler.player.use_youtube') }}</button>
                    </div>
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
                                x-bind:class="{ 'bg-white/10': current === {{ $index }}, 'opacity-40': source === 'youtube' && missing.includes({{ $index }}) }"
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
                                <a href="{{ $track['url'] }}" target="_blank" rel="noopener" x-on:click.stop class="shrink-0 rounded-md px-1.5 py-1 text-xs text-white/40 opacity-0 transition group-hover:opacity-100 hover:text-white focus-visible:opacity-100" title="{{ __('moodler.player.open') }}" aria-label="{{ __('moodler.player.open') }}">↗</a>
                            </div>
                        </li>
                    @endforeach
                </ol>

                {{-- Saving: what it offers depends on whether the player is signed in and subscribed. --}}
                <div class="mt-5 rounded-2xl border border-white/10 bg-ink-950/40 p-4" wire:key="save-panel-{{ $this->saveMode() }}">
                    @if ($this->saveMode() === 'guest')
                        <p class="text-sm text-white/65">{{ __('moodler.save.guest_pitch') }}</p>
                        <button type="button" wire:click="signInToSave" class="mt-3 inline-flex w-full items-center justify-center rounded-xl bg-white/10 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/15">
                            {{ __('moodler.save.sign_in') }}
                        </button>
                    @elseif ($this->saveMode() === 'subscribe')
                        <p class="text-sm text-white/65">{{ __('moodler.save.subscribe_pitch') }}</p>
                        <button type="button" wire:click="startCheckout" wire:loading.attr="disabled" wire:target="startCheckout" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-ember-400 px-4 py-2.5 text-sm font-semibold text-ink-950 transition hover:bg-ember-300 disabled:opacity-60">
                            <span wire:loading.remove wire:target="startCheckout">{{ __('moodler.save.subscribe', ['price' => __('moodler.checkout.price')]) }}</span>
                            <span wire:loading wire:target="startCheckout">{{ __('moodler.checkout.opening') }}</span>
                        </button>
                        @if ($errorKey === 'checkout')
                            <p class="mt-3 rounded-xl border border-red-400/30 bg-red-500/10 px-3 py-2 text-sm text-red-200" role="alert">{{ __('moodler.errors.checkout') }}</p>
                        @endif
                    @else
                        <form wire:submit="savePlaylist({{ $this->openedPlaylistName === null ? 'true' : 'false' }})" class="space-y-3">
                            <label for="playlist-name" class="block text-xs font-semibold tracking-widest text-white/40 uppercase">{{ __('moodler.save.name') }}</label>
                            <input
                                id="playlist-name"
                                type="text"
                                wire:model="playlistName"
                                maxlength="{{ \App\Models\Playlist::MAX_NAME_LENGTH }}"
                                placeholder="{{ $composedPrompt['phrase'] ?? $phrase }}"
                                class="w-full rounded-xl border border-white/10 bg-ink-950/60 px-3 py-2 text-sm text-white placeholder:text-white/30 focus:border-ember-400 focus:ring-2 focus:ring-ember-400/30 focus:outline-none"
                            >
                            <p class="text-xs text-white/35">{{ __('moodler.save.name_hint') }}</p>
                            @error('playlistName') <p class="text-sm text-red-300">{{ $message }}</p> @enderror

                            <div class="flex flex-wrap gap-2">
                                @if ($this->openedPlaylistName !== null)
                                    <button type="submit" class="flex-1 rounded-xl bg-ember-400 px-4 py-2.5 text-sm font-semibold text-ink-950 transition hover:bg-ember-300">
                                        {{ __('moodler.save.overwrite', ['name' => Str::limit($this->openedPlaylistName, 40)]) }}
                                    </button>
                                    <button type="button" wire:click="savePlaylist(true)" class="flex-1 rounded-xl bg-white/10 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/15">
                                        {{ __('moodler.save.as_new') }}
                                    </button>
                                @else
                                    <button type="submit" class="flex-1 rounded-xl bg-ember-400 px-4 py-2.5 text-sm font-semibold text-ink-950 transition hover:bg-ember-300">
                                        {{ __('moodler.save.save') }}
                                    </button>
                                @endif
                            </div>

                            @if ($this->hasPendingChanges)
                                <p class="text-xs text-white/45">{{ __('moodler.save.pending_note') }}</p>
                            @endif
                            @if ($playlistNotice)
                                <p class="rounded-xl border border-emerald-300/20 bg-emerald-300/10 px-3 py-2 text-sm text-emerald-100" role="status" wire:key="playlist-notice-{{ $playlistNotice }}">
                                    {{ __("moodler.save.{$playlistNotice}") }}
                                    <a href="{{ route('playlists.index') }}" wire:navigate class="ml-1 underline underline-offset-4">{{ __('moodler.save.view_all') }}</a>
                                </p>
                            @endif
                        </form>
                    @endif
                </div>

                <p x-show="source === 'spotify'" class="mt-4 text-center text-xs text-white/35">{{ __('moodler.player.full_tracks') }}</p>
                <p x-show="source === 'youtube'" x-cloak class="mt-4 text-center text-xs text-white/35">{{ __('moodler.player.youtube_note') }}</p>

                <div wire:loading.flex wire:target="compose" class="absolute inset-0 z-10 flex-col items-center justify-center gap-3 rounded-3xl bg-ink-950/80 backdrop-blur-sm">
                    <span class="size-8 animate-spin rounded-full border-2 border-white/15 border-t-ember-400"></span>
                    <span class="text-sm text-white/70">{{ __('moodler.player.mixing') }}</span>
                </div>
            </section>
        @endif
    </div>
</div>
