<?php

namespace App\Livewire;

use App\Data\CatalogEntry;
use App\Data\MoodProfile;
use App\Data\Track;
use App\Data\Tuning;
use App\Http\Middleware\SetLocale;
use App\Services\CatalogSearch;
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

    private const MIN_SEARCH_LENGTH = 2;

    private const MAX_SEARCH_LENGTH = 40;

    private const EMPTY_CATALOG_SEARCHES = [
        'moods' => ['query' => '', 'results' => [], 'failed' => false],
        'genres' => ['query' => '', 'results' => [], 'failed' => false],
    ];

    /**
     * Atmosphere fields that accept several values, with how many each allows.
     */
    private const MULTIPLE_CHOICE_LIMITS = [
        'moods' => MoodProfile::MAX_MOODS,
        'genres' => MoodProfile::MAX_GENRES,
    ];

    /**
     * The furthest section revealed so far. Sections stay on screen once shown.
     */
    public int $step = self::STEP_PHRASE;

    public string $phrase = '';

    public string $dayTime = '';

    /** @var list<string> */
    public array $moods = [];

    public string $relationship = '';

    /** @var list<string> */
    public array $genres = [];

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

    /**
     * The latest catalog search per field: what was typed, what came back, and whether the search failed.
     *
     * @var array<'moods'|'genres', array{query: string, results: list<array{value: string, label: string, description: string}>, failed: bool}>
     */
    #[Locked]
    public array $catalogSearches = self::EMPTY_CATALOG_SEARCHES;

    /**
     * Labels and descriptions of options picked from a catalog search, by value.
     *
     * @var array<string, array{label: string, description: string}>
     */
    #[Locked]
    public array $customOptions = [];

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
        $this->catalogSearches = self::EMPTY_CATALOG_SEARCHES;
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
            $interpretation = $interpreter->interpret($this->phrase, App::getLocale());
        } catch (RuntimeException $exception) {
            report($exception);
            $this->errorKey = 'interpret';

            return;
        }

        $this->dayTime = $interpretation->profile->dayTime;
        $this->moods = $interpretation->profile->moods;
        $this->relationship = $interpretation->profile->relationship;
        $this->genres = $interpretation->profile->genres;
        $this->customOptions = [...$this->customOptions, ...$interpretation->customOptions];
        $this->energy = $interpretation->tuning->energy;
        $this->popularity = $interpretation->tuning->popularity;
        $this->instrumentalness = $interpretation->tuning->instrumentalness;
        if ($this->step < self::STEP_ATMOSPHERE) {
            $this->step = self::STEP_ATMOSPHERE;
            $this->dispatch('section-revealed', section: 'atmosphere');
        }
    }

    /**
     * Build a new track list from the current settings, avoiding songs already heard.
     */
    public function compose(PlaylistComposer $composer): void
    {
        $this->validate([
            'phrase' => self::PHRASE_RULES,
            'dayTime' => ['required', 'string', 'max:40'],
            'moods' => ['required', 'array', 'max:'.MoodProfile::MAX_MOODS],
            'moods.*' => ['required', 'string', 'max:40', 'distinct'],
            'relationship' => ['required', 'string', 'max:40'],
            'genres' => ['required', 'array', 'max:'.MoodProfile::MAX_GENRES],
            'genres.*' => ['required', 'string', 'max:40', 'distinct'],
            'energy' => ['required', 'integer', 'between:0,100'],
            'popularity' => ['required', 'integer', 'between:0,100'],
            'instrumentalness' => ['required', 'integer', 'between:0,100'],
        ], $this->phraseMessages());
        $this->errorKey = null;
        $this->step = self::STEP_TUNING;

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

        $isFirstList = $this->tracks === [];
        $this->tracks = array_map(fn (Track $track): array => $track->toArray(), $tracks);
        $this->heardSongs = array_slice(array_values(array_unique([
            ...array_map(fn (Track $track): string => "{$track->title} - {$track->artists}", $tracks),
            ...$this->heardSongs,
        ])), 0, self::MAX_HEARD_SONGS);
        $this->composedSignature = $this->signature();

        $this->dispatch('tracks-updated', uris: array_column($this->tracks, 'uri'));

        if ($isFirstList) {
            $this->dispatch('section-revealed', section: 'player');
        }
    }

    /**
     * Add or remove a mood or genre. At least one stays selected, and no more than the limit.
     */
    public function toggleOption(string $field, string $value): void
    {
        $limit = self::MULTIPLE_CHOICE_LIMITS[$field] ?? null;

        if ($limit === null) {
            return;
        }

        $selected = $this->{$field};

        if (in_array($value, $selected, true)) {
            if (count($selected) > 1) {
                $this->{$field} = array_values(array_diff($selected, [$value]));
            }

            return;
        }

        if (count($selected) < $limit) {
            $this->{$field} = [...$selected, $value];
            $this->rememberCatalogOption($field, $value);
        }
    }

    /**
     * Look up moods or genres beyond the built-in options that match what the player typed.
     */
    public function searchCatalog(CatalogSearch $catalog, string $field, string $query): void
    {
        if (! array_key_exists($field, self::EMPTY_CATALOG_SEARCHES)) {
            return;
        }

        $query = mb_substr(trim($query), 0, self::MAX_SEARCH_LENGTH);
        $search = ['query' => $query, 'results' => [], 'failed' => false];

        if (mb_strlen($query) >= self::MIN_SEARCH_LENGTH) {
            try {
                $search['results'] = array_map(
                    fn (CatalogEntry $entry): array => $entry->toArray(),
                    $catalog->search($field, $query, App::getLocale()),
                );
            } catch (RuntimeException $exception) {
                report($exception);
                $search['failed'] = true;
            }
        }

        $this->catalogSearches[$field] = $search;
    }

    /**
     * Search results that are not already shown among the options.
     *
     * @return list<array{value: string, label: string, description: string}>
     */
    public function catalogResults(string $field): array
    {
        $shown = array_map(mb_strtolower(...), MoodCategories::withValues($this->defaultOptions($field), $this->{$field}));

        return array_values(array_filter(
            $this->catalogSearches[$field]['results'] ?? [],
            fn (array $result): bool => ! in_array(mb_strtolower($result['value']), $shown, true),
        ));
    }

    /**
     * Show the tuning section below the atmosphere.
     */
    public function revealTuning(): void
    {
        if ($this->hasProfile() && $this->step < self::STEP_TUNING) {
            $this->step = self::STEP_TUNING;
            $this->dispatch('section-revealed', section: 'tuning');
        }
    }

    public function restart(): void
    {
        $this->reset();
        $this->locale = App::getLocale();
        $this->dispatch('tracks-cleared');
    }

    public function showsAtmosphere(): bool
    {
        return $this->step >= self::STEP_ATMOSPHERE && $this->hasProfile();
    }

    public function showsTuning(): bool
    {
        return $this->step >= self::STEP_TUNING && $this->hasProfile();
    }

    public function hasProfile(): bool
    {
        return $this->dayTime !== '' && $this->moods !== [] && $this->relationship !== '' && $this->genres !== [];
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
     * Moods and genres accept several values (`limit` set); time of day and company accept one.
     *
     * @return array<string, array{label: string, limit: ?int, selected: list<string>, options: list<array{value: string, label: string, description: ?string}>}>
     */
    public function atmosphereGroups(): array
    {
        return collect(['moods', 'genres', 'dayTime', 'relationship'])->mapWithKeys(fn (string $field): array => [$field => [
            'label' => __("moodler.atmosphere.groups.{$field}"),
            'limit' => self::MULTIPLE_CHOICE_LIMITS[$field] ?? null,
            'selected' => (array) $this->{$field},
            'options' => array_map(
                fn (string $option): array => [
                    'value' => $option,
                    'label' => $this->customOptions[$option]['label'] ?? self::optionLabel($option),
                    'description' => $this->optionDescription($option),
                ],
                MoodCategories::withValues($this->defaultOptions($field), $this->{$field}),
            ),
        ]])->all();
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

    /**
     * What a mood or genre sounds like, when known: built-in ones are described in the
     * language files, options proposed by the AI or found by a search keep the description it gave.
     */
    private function optionDescription(string $option): ?string
    {
        if (filled($this->customOptions[$option]['description'] ?? null)) {
            return $this->customOptions[$option]['description'];
        }

        foreach (['mood_descriptions', 'genre_descriptions'] as $group) {
            if (Lang::has($key = "moodler.{$group}.{$option}")) {
                return __($key);
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function defaultOptions(string $field): array
    {
        return match ($field) {
            'moods' => MoodCategories::MOODS,
            'genres' => MoodCategories::GENRES,
            'dayTime' => MoodCategories::DAY_TIMES,
            'relationship' => MoodCategories::RELATIONSHIPS,
        };
    }

    /**
     * Keep the label and description of an option picked from a search, so its chip stays readable.
     */
    private function rememberCatalogOption(string $field, string $value): void
    {
        foreach ($this->catalogSearches[$field]['results'] ?? [] as $result) {
            if ($result['value'] === $value) {
                $this->customOptions[$value] = ['label' => $result['label'], 'description' => $result['description']];

                return;
            }
        }
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
        return new MoodProfile($this->dayTime, $this->moods, $this->relationship, $this->genres);
    }

    private function tuning(): Tuning
    {
        return new Tuning($this->energy, $this->popularity, $this->instrumentalness);
    }

    private function signature(): string
    {
        return md5(json_encode([
            $this->phrase, $this->dayTime, $this->moods, $this->relationship, $this->genres,
            $this->energy, $this->popularity, $this->instrumentalness,
        ], JSON_THROW_ON_ERROR));
    }
}
