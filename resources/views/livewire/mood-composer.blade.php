<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:py-12">
    <header class="mb-8 flex flex-wrap items-center justify-between gap-x-6 gap-y-3">
        <div class="flex min-w-0 items-baseline gap-3">
            <h1 class="font-display text-3xl font-semibold tracking-tight text-white">moodler</h1>
            <p class="hidden text-sm text-white/50 sm:block">{{ __('moodler.tagline') }}</p>
        </div>

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
    </header>

    <div @class([
        'grid items-start gap-8 lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]' => $this->hasList,
        'mx-auto max-w-xl' => ! $this->hasList,
    ])>
        {{-- Left: the game board --}}
        <section class="rounded-3xl border border-white/10 bg-ink-900/80 p-5 shadow-2xl shadow-black/30 backdrop-blur sm:p-7">
            <nav class="mb-6 grid grid-cols-3 gap-2" aria-label="{{ __('moodler.steps.label') }}">
                @foreach ([1 => __('moodler.steps.moment'), 2 => __('moodler.steps.atmosphere'), 3 => __('moodler.steps.tuning')] as $number => $label)
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

            @if ($errorKey)
                <div class="mb-5 rounded-2xl border border-red-400/30 bg-red-500/10 px-4 py-3 text-sm text-red-200" role="alert">
                    {{ __("moodler.errors.{$errorKey}") }}
                </div>
            @endif

            @if ($step === 1)
                <form wire:submit="interpret" class="space-y-4" wire:key="step-phrase">
                    <div>
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

                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-ember-400 px-5 py-3 font-semibold text-ink-950 transition hover:bg-ember-300 disabled:opacity-60" wire:loading.attr="disabled" wire:target="interpret">
                        <span wire:loading.remove wire:target="interpret">{{ $this->hasProfile() ? __('moodler.phrase.submit_again') : __('moodler.phrase.submit') }}</span>
                        <span wire:loading wire:target="interpret">{{ __('moodler.phrase.submitting') }}</span>
                    </button>
                    @if ($this->hasProfile())
                        <p class="text-center text-xs text-white/40">{{ __('moodler.phrase.replace_note') }}</p>
                    @endif
                </form>
            @elseif ($step === 2)
                <div class="space-y-6" wire:key="step-atmosphere">
                    <div>
                        <h2 class="font-display text-2xl font-medium text-white">{{ __('moodler.atmosphere.title') }}</h2>
                        <p class="mt-1 text-sm text-white/50">{{ __('moodler.atmosphere.subtitle') }}</p>
                    </div>

                    @foreach ($this->atmosphereGroups() as $field => $group)
                        <fieldset wire:key="group-{{ $field }}">
                            <legend class="mb-2 text-xs font-semibold tracking-widest text-white/40 uppercase">
                                {{ $group['label'] }}
                                @if ($group['limit'])
                                    <span class="ml-1 font-normal tracking-normal normal-case text-white/30">· {{ __('moodler.atmosphere.pick_up_to', ['max' => $group['limit']]) }}</span>
                                @endif
                            </legend>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($group['options'] as $option)
                                    @if ($group['limit'])
                                        @php($isSelected = in_array($option['value'], $group['selected'], true))
                                        <button
                                            type="button"
                                            wire:key="{{ $field }}-{{ $option['value'] }}"
                                            wire:click="toggleOption('{{ $field }}', {{ Js::from($option['value']) }})"
                                            aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                                            @disabled(! $isSelected && count($group['selected']) >= $group['limit'])
                                            class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-sm text-white/75 transition hover:bg-white/10 focus-visible:ring-2 focus-visible:ring-ember-300 focus-visible:outline-none aria-pressed:border-ember-400 aria-pressed:bg-ember-400 aria-pressed:text-ink-950 disabled:cursor-not-allowed disabled:opacity-35 disabled:hover:bg-white/5"
                                        >{{ $option['label'] }}</button>
                                    @else
                                        <label wire:key="{{ $field }}-{{ $option['value'] }}" class="cursor-pointer">
                                            <input type="radio" class="peer sr-only" wire:model.live="{{ $field }}" value="{{ $option['value'] }}">
                                            <span class="inline-block rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-sm text-white/75 transition peer-checked:border-ember-400 peer-checked:bg-ember-400 peer-checked:text-ink-950 peer-focus-visible:ring-2 peer-focus-visible:ring-ember-300 hover:bg-white/10">{{ $option['label'] }}</span>
                                        </label>
                                    @endif
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach

                    @unless ($this->hasList)
                        <button type="button" wire:click="goToStep(3)" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-white/10 px-5 py-3 font-semibold text-white transition hover:bg-white/15">
                            {{ __('moodler.atmosphere.next') }} <span aria-hidden="true">→</span>
                        </button>
                    @endunless
                </div>
            @else
                <div class="space-y-6" wire:key="step-tuning">
                    <div>
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
            @if ($this->hasProfile() && ($step > 1 || $this->hasList))
                <div class="mt-8 space-y-3 border-t border-white/10 pt-5">
                    @if ($this->hasPendingChanges)
                        <p class="flex items-center gap-2 text-xs text-ember-300" wire:key="pending-changes">
                            <span class="size-1.5 animate-pulse rounded-full bg-ember-400"></span>
                            {{ __('moodler.actions.pending') }}
                        </p>
                    @endif

                    @if ($this->hasList || $step === 3)
                        <button type="button" wire:click="compose" wire:loading.attr="disabled" wire:target="compose" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-ember-400 to-dusk-400 px-5 py-3.5 font-semibold text-ink-950 shadow-lg shadow-ember-500/20 transition hover:brightness-110 disabled:opacity-60">
                            <span wire:loading.remove wire:target="compose">{{ $this->hasList ? __('moodler.actions.remix') : __('moodler.actions.create') }}</span>
                            <span wire:loading wire:target="compose">{{ __('moodler.actions.mixing') }}</span>
                        </button>
                    @endif

                    <button type="button" wire:click="restart" @if ($this->hasList) wire:confirm="{{ __('moodler.actions.restart_confirm') }}" @endif class="w-full rounded-2xl px-5 py-2 text-sm text-white/50 transition hover:bg-white/5 hover:text-white/80">
                        {{ __('moodler.actions.restart') }}
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
                        <p class="text-xs font-semibold tracking-widest text-white/40 uppercase">{{ __('moodler.player.your_list') }}</p>
                        <p class="mt-1 truncate font-display text-xl text-white">“{{ $phrase }}”</p>
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
                                <a href="{{ $track['url'] }}" target="_blank" rel="noopener" x-on:click.stop class="shrink-0 rounded-md px-1.5 py-1 text-xs text-white/40 opacity-0 transition group-hover:opacity-100 hover:text-white focus-visible:opacity-100" title="{{ __('moodler.player.open') }}" aria-label="{{ __('moodler.player.open') }}">↗</a>
                            </div>
                        </li>
                    @endforeach
                </ol>

                <p class="mt-4 text-center text-xs text-white/35">{{ __('moodler.player.full_tracks') }}</p>

                <div wire:loading.flex wire:target="compose" class="absolute inset-0 z-10 flex-col items-center justify-center gap-3 rounded-3xl bg-ink-950/80 backdrop-blur-sm">
                    <span class="size-8 animate-spin rounded-full border-2 border-white/15 border-t-ember-400"></span>
                    <span class="text-sm text-white/70">{{ __('moodler.player.mixing') }}</span>
                </div>
            </section>
        @endif
    </div>
</div>
