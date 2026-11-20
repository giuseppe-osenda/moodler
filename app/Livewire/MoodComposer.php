<?php

namespace App\Livewire;

use App\Data\MoodProfile;
use App\Data\Track;
use App\Data\Tuning;
use App\Services\MoodInterpreter;
use App\Services\PlaylistComposer;
use App\Support\MoodCategories;
use Illuminate\Contracts\View\View;
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

    #[Locked]
    public ?string $errorMessage = null;

    /**
     * Read the phrase and propose an atmosphere for it.
     */
    public function interpret(MoodInterpreter $interpreter): void
    {
        $this->validate(['phrase' => ['required', 'string', 'min:3', 'max:280']]);
        $this->errorMessage = null;

        try {
            $profile = $interpreter->interpret($this->phrase);
        } catch (RuntimeException $exception) {
            report($exception);
            $this->errorMessage = 'We could not read your mood right now. Please try again in a moment.';

            return;
        }

        $this->dayTime = $profile->dayTime;
        $this->mood = $profile->mood;
        $this->relationship = $profile->relationship;
        $this->genre = $profile->genre;
        $this->step = self::STEP_ATMOSPHERE;
    }

    /**
     * Build a new track list from the current settings, avoiding songs already heard.
     */
    public function compose(PlaylistComposer $composer): void
    {
        $this->validate([
            'phrase' => ['required', 'string', 'min:3', 'max:280'],
            'dayTime' => ['required', 'string', 'max:40'],
            'mood' => ['required', 'string', 'max:40'],
            'relationship' => ['required', 'string', 'max:40'],
            'genre' => ['required', 'string', 'max:40'],
            'energy' => ['required', 'integer', 'between:0,100'],
            'popularity' => ['required', 'integer', 'between:0,100'],
            'instrumentalness' => ['required', 'integer', 'between:0,100'],
        ]);
        $this->errorMessage = null;

        try {
            $tracks = $composer->compose($this->phrase, $this->profile(), $this->tuning(), self::LIST_SIZE, $this->heardSongs);
        } catch (RuntimeException $exception) {
            report($exception);
            $this->errorMessage = 'Something went wrong while picking your songs. Please try again.';

            return;
        }

        if ($tracks === []) {
            $this->errorMessage = 'No songs matched this mix. Try changing a few settings and remix.';

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
     * @return array<string, array{label: string, options: list<string>}>
     */
    #[Computed]
    public function atmosphereGroups(): array
    {
        return [
            'mood' => ['label' => 'Mood', 'options' => MoodCategories::withValue(MoodCategories::MOODS, $this->mood)],
            'genre' => ['label' => 'Genre', 'options' => MoodCategories::withValue(MoodCategories::GENRES, $this->genre)],
            'dayTime' => ['label' => 'Time of day', 'options' => MoodCategories::withValue(MoodCategories::DAY_TIMES, $this->dayTime)],
            'relationship' => ['label' => 'Listening with', 'options' => MoodCategories::withValue(MoodCategories::RELATIONSHIPS, $this->relationship)],
        ];
    }

    /**
     * @return array<string, array{label: string, low: string, high: string}>
     */
    #[Computed]
    public function tuningKnobs(): array
    {
        return [
            'energy' => ['label' => 'Energy', 'low' => 'Calm', 'high' => 'Intense'],
            'popularity' => ['label' => 'Popularity', 'low' => 'Hidden gems', 'high' => 'Big hits'],
            'instrumentalness' => ['label' => 'Vocals', 'low' => 'Sung', 'high' => 'Instrumental'],
        ];
    }

    public function render(): View
    {
        return view('livewire.mood-composer');
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
