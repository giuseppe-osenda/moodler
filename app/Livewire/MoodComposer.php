<?php

namespace App\Livewire;

use App\Data\MoodProfile;
use App\Data\Track;
use App\Data\Tuning;
use App\Http\Middleware\SetLocale;
use App\Services\MoodInterpreter;
use App\Services\PlaylistComposer;
use App\Support\MoodCategories;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Lang;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;

#[Title('Moodler')]
class MoodComposer extends Component
{
    public const STEP_PHRASE = 1;

    public const STEP_ATMOSPHERE = 2;

    public const STEP_TUNING = 3;

    private const LIST_SIZE = 15;

    private const MAX_HEARD_SONGS = 45;

    private const PHRASE_RULES = ['required', 'string', 'min:3', 'max:280'];

    private const LOCALE_COOKIE_MINUTES = 60 * 24 * 365;

    public int $step = self::STEP_PHRASE;

    public string $phrase = '';

    public string $dayTime = '';

    public string $mood = '';

    public string $relationship = '';

    public string $genre = '';

    public int $energy = 50;

    public int $popularity = 50;

    public int $instrumentalness = 20;

    /** @var list<array{id: string, uri: string, url: string, title: string, artists: string, album: string, imageUrl: ?string, durationMs: int}> */
    #[Locked]
    public array $tracks = [];

    /** @var list<string> */
    #[Locked]
    public array $heardSongs = [];

    #[Locked]
    public ?string $composedSignature = null;

    /** @var 'interpret'|'compose'|'no_tracks'|null */
    #[Locked]
    public ?string $errorKey = null;

    public string $locale = '';

    public function mount(): void
    {
        $this->locale = App::getLocale();
    }

    /**
     * Switch the interface language and remember the choice in a cookie.
     */
    public function updatedLocale(string $locale): void
    {
        if (! SetLocale::isSupported($locale)) {
            $this->locale = App::getLocale();

            return;
        }

        Cookie::queue(SetLocale::COOKIE, $locale, self::LOCALE_COOKIE_MINUTES);
        App::setLocale($locale);
        $this->dispatch('locale-changed', locale: $locale);
    }

    /**
     * Read the phrase and propose an atmosphere and knob settings for it.
     */
    public function interpret(MoodInterpreter $interpreter): void
    {
        $this->validate(['phrase' => self::PHRASE_RULES], $this->phraseMessages());
        $this->errorKey = null;

        try {
            $interpretation = $interpreter->interpret($this->phrase);
        } catch (RuntimeException $exception) {
            report($exception);
            $this->errorKey = 'interpret';

            return;
        }

        $this->dayTime = $interpretation->profile->dayTime;
        $this->mood = $interpretation->profile->mood;
        $this->relationship = $interpretation->profile->relationship;
        $this->genre = $interpretation->profile->genre;
        $this->energy = $interpretation->tuning->energy;
        $this->popularity = $interpretation->tuning->popularity;
        $this->instrumentalness = $interpretation->tuning->instrumentalness;
        $this->step = self::STEP_ATMOSPHERE;
    }

    /**
     * Build a new track list from the current settings, avoiding songs already heard.
     */
    public function compose(PlaylistComposer $composer): void
    {
        $this->validate([
            'phrase' => self::PHRASE_RULES,
            'dayTime' => ['required', 'string', 'max:40'],
            'mood' => ['required', 'string', 'max:40'],
            'relationship' => ['required', 'string', 'max:40'],
            'genre' => ['required', 'string', 'max:40'],
            'energy' => ['required', 'integer', 'between:0,100'],
            'popularity' => ['required', 'integer', 'between:0,100'],
            'instrumentalness' => ['required', 'integer', 'between:0,100'],
        ], $this->phraseMessages());
        $this->errorKey = null;

        try {
            $tracks = $composer->compose($this->phrase, $this->profile(), $this->tuning(), self::LIST_SIZE, $this->heardSongs);
        } catch (RuntimeException $exception) {
            report($exception);
            $this->errorKey = 'compose';

            return;
        }

        if ($tracks === []) {
            $this->errorKey = 'no_tracks';

            return;
        }

        $this->tracks = array_map(fn (Track $track): array => $track->toArray(), $tracks);
        $this->heardSongs = array_slice(array_values(array_unique([
            ...array_map(fn (Track $track): string => "{$track->title} - {$track->artists}", $tracks),
            ...$this->heardSongs,
        ])), 0, self::MAX_HEARD_SONGS);
        $this->composedSignature = $this->signature();

        $this->dispatch('tracks-updated', uris: array_column($this->tracks, 'uri'));
    }

    public function goToStep(int $step): void
    {
        if ($step === self::STEP_PHRASE || ($this->hasProfile() && in_array($step, [self::STEP_ATMOSPHERE, self::STEP_TUNING], true))) {
            $this->step = $step;
        }
    }

    public function restart(): void
    {
        $this->reset();
        $this->locale = App::getLocale();
        $this->dispatch('tracks-cleared');
    }

    public function hasProfile(): bool
    {
        return $this->dayTime !== '' && $this->mood !== '' && $this->relationship !== '' && $this->genre !== '';
    }

    /**
     * Whether a track list has been composed at least once.
     */
    #[Computed]
    public function hasList(): bool
    {
        return $this->tracks !== [];
    }

    /**
     * Whether the settings changed since the current list was composed.
     */
    #[Computed]
    public function hasPendingChanges(): bool
    {
        return $this->composedSignature !== null && $this->composedSignature !== $this->signature();
    }

    /**
     * Mood options per dimension. Values stay in English for the AI; labels follow the interface language.
     *
     * @return array<string, array{label: string, options: list<array{value: string, label: string}>}>
     */
    public function atmosphereGroups(): array
    {
        $groups = [
            'mood' => MoodCategories::withValue(MoodCategories::MOODS, $this->mood),
            'genre' => MoodCategories::withValue(MoodCategories::GENRES, $this->genre),
            'dayTime' => MoodCategories::withValue(MoodCategories::DAY_TIMES, $this->dayTime),
            'relationship' => MoodCategories::withValue(MoodCategories::RELATIONSHIPS, $this->relationship),
        ];

        return collect($groups)->map(fn (array $options, string $field): array => [
            'label' => __("moodler.atmosphere.groups.{$field}"),
            'options' => array_map(fn (string $option): array => ['value' => $option, 'label' => self::optionLabel($option)], $options),
        ])->all();
    }

    /**
     * @return array<string, array{label: string, low: string, high: string}>
     */
    public function tuningKnobs(): array
    {
        return __('moodler.tuning.knobs');
    }

    /**
     * @return array<string, string>
     */
    public function supportedLocales(): array
    {
        return config('app.supported_locales');
    }

    public static function optionLabel(string $option): string
    {
        $key = "moodler.options.{$option}";

        return Lang::has($key) ? __($key) : $option;
    }

    public function render(): View
    {
        return view('livewire.mood-composer');
    }

    /**
     * @return array<string, string>
     */
    private function phraseMessages(): array
    {
        return [
            'phrase.required' => __('moodler.validation.phrase_required'),
            'phrase.min' => __('moodler.validation.phrase_min'),
            'phrase.max' => __('moodler.validation.phrase_max'),
        ];
    }

    private function profile(): MoodProfile
    {
        return new MoodProfile($this->dayTime, $this->mood, $this->relationship, $this->genre);
    }

    private function tuning(): Tuning
    {
        return new Tuning($this->energy, $this->popularity, $this->instrumentalness);
    }

    private function signature(): string
    {
        return md5(json_encode([
            $this->phrase, $this->dayTime, $this->mood, $this->relationship, $this->genre,
            $this->energy, $this->popularity, $this->instrumentalness,
        ], JSON_THROW_ON_ERROR));
    }
}
