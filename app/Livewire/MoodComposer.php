<?php

namespace App\Livewire;

use App\Actions\StartSubscriptionCheckout;
use App\Data\CatalogEntry;
use App\Data\MoodProfile;
use App\Data\Track;
use App\Data\Tuning;
use App\Exceptions\YouTubeUnavailable;
use App\Http\Middleware\SetLocale;
use App\Livewire\Concerns\AwaitsSubscription;
use App\Models\Playlist;
use App\Models\User;
use App\Services\CatalogSearch;
use App\Services\ListeningHistory;
use App\Services\MoodInterpreter;
use App\Services\PlaylistComposer;
use App\Services\YouTubeClient;
use App\Support\MoodCategories;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;

#[Title('Moodler')]
class MoodComposer extends Component
{
    use AwaitsSubscription;

    public const STEP_PHRASE = 1;

    public const STEP_ATMOSPHERE = 2;

    public const STEP_TUNING = 3;

    private const LIST_SIZE = 15;

    private const MAX_HEARD_SONGS = 45;

    /**
     * Songs the AI is asked to avoid at most: those heard in this visit plus those given for the same prompt before.
     */
    private const MAX_AVOIDED_SONGS = 90;

    /**
     * Where the latest list is kept, so it survives signing in and paying.
     */
    private const SESSION_KEY = 'moodler.composition';

    private const PHRASE_RULES = ['required', 'string', 'min:3', 'max:280'];

    private const LOCALE_COOKIE_MINUTES = 60 * 24 * 365;

    /**
     * YouTube searches one visitor may spend per hour, so nobody drains the shared daily quota.
     * Songs already looked up are free, and the player only searches the track the listener settles on.
     */
    private const YOUTUBE_LOOKUPS_PER_HOUR = 100;

    /**
     * AI readings and track lists one visitor may ask for per hour, together: each one is a paid AI call.
     * Plenty for someone remixing a list, low enough that a bot cannot run up the bill.
     */
    private const MIXES_PER_HOUR = 30;

    /**
     * AI searches for extra moods and genres one visitor may run per hour.
     */
    private const CATALOG_SEARCHES_PER_HOUR = 60;

    /**
     * Videos of one track the player may report as unplayable in a single lookup.
     */
    private const MAX_UNPLAYABLE_VIDEOS = 5;

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

    /**
     * The phrase and settings the current list was composed with.
     *
     * @var array{phrase: string, dayTime: string, moods: list<string>, relationship: string, genres: list<string>, energy: int, popularity: int, instrumentalness: int}|array{}
     */
    #[Locked]
    public array $composedPrompt = [];

    /**
     * The saved playlist this list started from, which saving overwrites unless saved as new.
     */
    #[Locked]
    public ?int $playlistId = null;

    public string $playlistName = '';

    /** @var 'saved'|'updated'|null */
    #[Locked]
    public ?string $playlistNotice = null;

    /** @var 'interpret'|'compose'|'no_tracks'|'checkout'|null */
    #[Locked]
    public ?string $errorKey = null;

    /**
     * Minutes left before a visitor who hit the hourly limit can ask the AI again.
     */
    #[Locked]
    public int $retryMinutes = 0;

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

    /**
     * Start empty, from a saved playlist, or from the list composed before signing in or paying.
     */
    public function mount(?Playlist $playlist = null): void
    {
        $this->locale = App::getLocale();

        if ($playlist !== null) {
            $this->authorize('view', $playlist);

            if (! $this->user()?->canSavePlaylists()) {
                session()->flash('status', __('moodler.library.subscription_needed'));
                $this->redirectRoute('account');

                return;
            }

            $this->loadPlaylist($playlist);

            return;
        }

        if (request()->boolean('restore')) {
            $this->restoreComposition();
        }

        $this->awaitSubscriptionAfterCheckout();
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

        if (! $this->spendAiBudget('mixes', self::MIXES_PER_HOUR)) {
            $this->errorKey = 'interpret_limited';

            return;
        }

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
    public function compose(PlaylistComposer $composer, ListeningHistory $history): void
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

        if (! $this->spendAiBudget('mixes', self::MIXES_PER_HOUR)) {
            $this->errorKey = 'compose_limited';

            return;
        }

        $prompt = $this->prompt();
        $avoid = array_slice(array_values(array_unique([
            ...$this->heardSongs,
            ...$history->songsAlreadyGiven($prompt, $this->user()),
        ])), 0, self::MAX_AVOIDED_SONGS);

        try {
            $tracks = $composer->compose($this->phrase, $this->profile(), $this->tuning(), self::LIST_SIZE, $avoid);
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
        $songs = array_map(fn (Track $track): string => "{$track->title} - {$track->artists}", $tracks);
        $history->record($prompt, $songs, $this->user());

        $this->tracks = array_map(fn (Track $track): array => $track->toArray(), $tracks);
        $this->heardSongs = array_slice(array_values(array_unique([...$songs, ...$this->heardSongs])), 0, self::MAX_HEARD_SONGS);
        $this->composedSignature = $this->signature();
        $this->composedPrompt = $prompt;
        $this->playlistNotice = null;
        $this->rememberComposition();

        $this->dispatch('tracks-updated', tracks: $this->playerTracks());

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
     * Apply the moods or genres the shuffle button drew. Only built-in options are accepted,
     * at least one and no more than the limit.
     *
     * @param  list<mixed>  $values
     */
    public function applyShuffle(string $field, array $values): void
    {
        $limit = self::MULTIPLE_CHOICE_LIMITS[$field] ?? null;

        if ($limit === null) {
            return;
        }

        $drawn = array_slice(array_values(array_unique(array_filter(
            $values,
            fn (mixed $value): bool => is_string($value) && in_array($value, $this->defaultOptions($field), true),
        ))), 0, $limit);

        if ($drawn !== []) {
            $this->{$field} = $drawn;
        }
    }

    /**
     * Apply the knob values the shuffle button drew, snapped to the sliders' steps of five.
     *
     * @param  array<mixed>  $values
     */
    public function applyTuningShuffle(array $values): void
    {
        foreach (array_keys($this->tuningKnobs()) as $knob) {
            if (is_numeric($values[$knob] ?? null)) {
                $this->{$knob} = (int) (round(max(0, min(100, (float) $values[$knob])) / 5) * 5);
            }
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

        if (mb_strlen($query) >= self::MIN_SEARCH_LENGTH && ! $this->spendAiBudget('catalog-searches', self::CATALOG_SEARCHES_PER_HOUR)) {
            $search['failed'] = true;
        } elseif (mb_strlen($query) >= self::MIN_SEARCH_LENGTH) {
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
     * Find the YouTube video for a track of the current list, right before it plays.
     *
     * Videos the player could not play (removed, blocked in this country, not allowed outside
     * YouTube) are skipped in favour of the song's next candidate, and moved behind the others.
     *
     * @param  list<mixed>  $unplayable  ids of this track's videos that failed to play
     * @return array{status: 'found', videoId: string}|array{status: 'limited', retryAfter: int}|array{status: 'missing'|'unavailable'}
     */
    #[Renderless]
    public function youtubeVideo(YouTubeClient $youtube, int $index, array $unplayable = []): array
    {
        $track = $this->tracks[$index] ?? null;

        if ($track === null) {
            return ['status' => 'missing'];
        }

        $artist = trim(explode(',', $track['artists'])[0]);
        $unplayable = array_slice(array_values(array_filter(
            $unplayable,
            fn (mixed $videoId): bool => is_string($videoId) && preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId) === 1,
        )), 0, self::MAX_UNPLAYABLE_VIDEOS);
        $limiterKey = 'youtube-lookup:'.request()->ip();

        if (! $youtube->isCached($track['title'], $artist)) {
            if (RateLimiter::tooManyAttempts($limiterKey, self::YOUTUBE_LOOKUPS_PER_HOUR)) {
                return ['status' => 'limited', 'retryAfter' => RateLimiter::availableIn($limiterKey)];
            }

            RateLimiter::hit($limiterKey, 3600);
        }

        try {
            $videoId = $youtube->findVideo($track['title'], $artist, $unplayable);
        } catch (YouTubeUnavailable $exception) {
            report($exception);

            return ['status' => 'unavailable'];
        }

        foreach ($unplayable as $unplayableId) {
            $youtube->demote($track['title'], $artist, $unplayableId);
        }

        return $videoId === null ? ['status' => 'missing'] : ['status' => 'found', 'videoId' => $videoId];
    }

    public function youtubeEnabled(): bool
    {
        return app(YouTubeClient::class)->isConfigured();
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
        session()->forget(self::SESSION_KEY);
        $this->dispatch('tracks-cleared');
    }

    /**
     * Save the current list as a playlist: over the one it was opened from, or as a new one.
     * Without a name, the playlist is named after the phrase.
     */
    public function savePlaylist(bool $asNew = false): void
    {
        $user = $this->user();

        if ($user === null) {
            $this->signInToSave();

            return;
        }

        if (! $user->canSavePlaylists() || $this->composedPrompt === []) {
            return;
        }

        $this->validate(
            ['playlistName' => ['nullable', 'string', 'max:'.Playlist::MAX_NAME_LENGTH]],
            ['playlistName.max' => __('moodler.save.name_too_long', ['max' => Playlist::MAX_NAME_LENGTH])],
        );

        $name = trim($this->playlistName) !== ''
            ? trim($this->playlistName)
            : Str::limit($this->composedPrompt['phrase'], Playlist::MAX_NAME_LENGTH - 1, '…');
        $attributes = [...$this->playlistAttributes(), 'name' => $name];
        $existing = ! $asNew && $this->playlistId !== null ? $user->playlists()->find($this->playlistId) : null;

        if ($existing !== null) {
            $existing->update($attributes);
            $this->playlistNotice = 'updated';
        } else {
            $this->playlistId = $user->playlists()->create($attributes)->id;
            $this->playlistNotice = 'saved';
        }

        $this->playlistName = $name;
        $this->rememberComposition();
    }

    /**
     * Send a guest to sign in, then back to this list.
     */
    public function signInToSave(): void
    {
        session()->put('url.intended', route('home', ['restore' => 1]));
        $this->redirectRoute('login');
    }

    /**
     * Open Paddle's checkout for the monthly subscription.
     */
    public function startCheckout(StartSubscriptionCheckout $checkout): void
    {
        $user = $this->user();

        if ($user === null) {
            $this->signInToSave();

            return;
        }

        if (! $user->hasVerifiedEmail()) {
            session()->put('url.intended', route('home', ['restore' => 1]));
            $this->redirectRoute('verification.notice');

            return;
        }

        if ($user->subscribed()) {
            return;
        }

        $this->errorKey = null;

        try {
            $options = $checkout($user, route('home', ['restore' => 1, 'subscribed' => 1]));
        } catch (Exception $exception) {
            report($exception);
            $this->errorKey = 'checkout';

            return;
        }

        $this->dispatch('paddle-checkout', options: $options);
    }

    /**
     * What the save panel offers: signing in, subscribing, or saving.
     *
     * @return 'guest'|'subscribe'|'save'
     */
    public function saveMode(): string
    {
        $user = $this->user();

        return match (true) {
            $user === null => 'guest',
            ! $user->canSavePlaylists() => 'subscribe',
            default => 'save',
        };
    }

    /**
     * The name of the playlist this list was opened from, if it still exists.
     */
    #[Computed]
    public function openedPlaylistName(): ?string
    {
        return $this->playlistId === null ? null : $this->user()?->playlists()->whereKey($this->playlistId)->value('name');
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
                    'builtIn' => in_array($option, $this->defaultOptions($field), true),
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

    /**
     * What the player needs per track: its page on Spotify, offered when YouTube cannot play it.
     * Videos themselves are looked up by position, only when a track is about to play.
     *
     * @return list<array{url: string}>
     */
    public function playerTracks(): array
    {
        return array_map(fn (array $track): array => ['url' => $track['url']], $this->tracks);
    }

    /**
     * Count one AI call against the visitor's hourly budget: the signed-in player's, or their IP address's.
     * When the budget is spent nothing is counted, and the minutes until it frees up are kept for the message.
     */
    private function spendAiBudget(string $budget, int $perHour): bool
    {
        $visitor = $this->user() !== null ? 'user:'.$this->user()->getKey() : 'ip:'.request()->ip();
        $key = "ai-{$budget}:{$visitor}";

        if (RateLimiter::tooManyAttempts($key, $perHour)) {
            $this->retryMinutes = max(1, (int) ceil(RateLimiter::availableIn($key) / 60));

            return false;
        }

        RateLimiter::hit($key, 3600);

        return true;
    }

    private function user(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    /**
     * @return array{phrase: string, dayTime: string, moods: list<string>, relationship: string, genres: list<string>, energy: int, popularity: int, instrumentalness: int}
     */
    private function prompt(): array
    {
        return [
            'phrase' => $this->phrase,
            'dayTime' => $this->dayTime,
            'moods' => $this->moods,
            'relationship' => $this->relationship,
            'genres' => $this->genres,
            'energy' => $this->energy,
            'popularity' => $this->popularity,
            'instrumentalness' => $this->instrumentalness,
        ];
    }

    /**
     * The composed prompt and its tracks, in the playlists table's columns.
     *
     * @return array<string, mixed>
     */
    private function playlistAttributes(): array
    {
        $prompt = $this->composedPrompt;
        $usedOptions = [...$prompt['moods'], ...$prompt['genres']];

        return [
            'phrase' => $prompt['phrase'],
            'day_time' => $prompt['dayTime'],
            'moods' => $prompt['moods'],
            'relationship' => $prompt['relationship'],
            'genres' => $prompt['genres'],
            'energy' => $prompt['energy'],
            'popularity' => $prompt['popularity'],
            'instrumentalness' => $prompt['instrumentalness'],
            'tracks' => $this->tracks,
            'custom_options' => array_intersect_key($this->customOptions, array_flip($usedOptions)) ?: null,
        ];
    }

    private function loadPlaylist(Playlist $playlist): void
    {
        $this->fillComposition([
            'prompt' => [
                'phrase' => $playlist->phrase,
                'dayTime' => $playlist->day_time,
                'moods' => $playlist->moods,
                'relationship' => $playlist->relationship,
                'genres' => $playlist->genres,
                'energy' => $playlist->energy,
                'popularity' => $playlist->popularity,
                'instrumentalness' => $playlist->instrumentalness,
            ],
            'tracks' => $playlist->tracks,
            'heardSongs' => array_map(fn (array $track): string => "{$track['title']} - {$track['artists']}", $playlist->tracks),
            'customOptions' => $playlist->custom_options ?? [],
            'playlistId' => $playlist->id,
            'playlistName' => $playlist->name,
        ]);
    }

    /**
     * Keep the latest list in the session, so signing in or paying does not lose it.
     */
    private function rememberComposition(): void
    {
        session()->put(self::SESSION_KEY, [
            'prompt' => $this->composedPrompt,
            'tracks' => $this->tracks,
            'heardSongs' => $this->heardSongs,
            'customOptions' => $this->customOptions,
            'playlistId' => $this->playlistId,
            'playlistName' => $this->playlistName,
        ]);
    }

    private function restoreComposition(): void
    {
        $composition = session(self::SESSION_KEY);

        if (! is_array($composition) || ($composition['tracks'] ?? []) === [] || ! is_array($composition['prompt'] ?? null)) {
            return;
        }

        $ownsPlaylist = isset($composition['playlistId'])
            && $this->user()?->playlists()->whereKey($composition['playlistId'])->exists();

        $this->fillComposition([
            ...$composition,
            'playlistId' => $ownsPlaylist ? $composition['playlistId'] : null,
            'playlistName' => $ownsPlaylist ? (string) ($composition['playlistName'] ?? '') : '',
        ]);
    }

    /**
     * @param  array{prompt: array{phrase: string, dayTime: string, moods: list<string>, relationship: string, genres: list<string>, energy: int, popularity: int, instrumentalness: int}, tracks: list<array<string, mixed>>, heardSongs: list<string>, customOptions: array<string, array{label: string, description: string}>, playlistId: ?int, playlistName: string}  $composition
     */
    private function fillComposition(array $composition): void
    {
        $prompt = $composition['prompt'];

        $this->phrase = $prompt['phrase'];
        $this->dayTime = $prompt['dayTime'];
        $this->moods = $prompt['moods'];
        $this->relationship = $prompt['relationship'];
        $this->genres = $prompt['genres'];
        $this->energy = $prompt['energy'];
        $this->popularity = $prompt['popularity'];
        $this->instrumentalness = $prompt['instrumentalness'];
        $this->tracks = $composition['tracks'];
        $this->heardSongs = $composition['heardSongs'];
        $this->customOptions = $composition['customOptions'];
        $this->playlistId = $composition['playlistId'];
        $this->playlistName = $composition['playlistName'];
        $this->composedPrompt = $prompt;
        $this->composedSignature = $this->signature();
        $this->step = self::STEP_TUNING;
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
