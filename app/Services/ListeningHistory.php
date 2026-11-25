<?php

namespace App\Services;

use App\Models\Guest;
use App\Models\PromptHistory;
use App\Models\User;
use App\Support\PromptFingerprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * Remembers which songs non-subscribers already got for a prompt, so asking again brings new ones.
 *
 * Guests are recognized by a token in a long-lived cookie, created the first time they get a list;
 * signed-in players by their account. Subscribers save what they like instead, so they are left out.
 */
class ListeningHistory
{
    public const COOKIE = 'moodler_guest';

    private const COOKIE_MINUTES = 60 * 24 * 365;

    /**
     * Past lists considered for a prompt: enough to cover several retries without bloating the AI prompt.
     */
    private const LISTS_CONSIDERED = 6;

    public function __construct(private Request $request) {}

    /**
     * "Title - Artists" of the songs already given for this prompt, most recent first.
     *
     * @param  array{phrase: string, dayTime: string, moods: list<string>, relationship: string, genres: list<string>, energy: int, popularity: int, instrumentalness: int}  $prompt
     * @return list<string>
     */
    public function songsAlreadyGiven(array $prompt, ?User $user): array
    {
        if ($this->isExempt($user)) {
            return [];
        }

        $query = PromptHistory::query()->where('fingerprint', PromptFingerprint::of($prompt));

        if ($user !== null) {
            $query->where('user_id', $user->id);
        } elseif ($guest = $this->currentGuest()) {
            $query->where('guest_id', $guest->id);
        } else {
            return [];
        }

        return $query->latest('id')
            ->limit(self::LISTS_CONSIDERED)
            ->pluck('songs')
            ->flatten()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Store the songs a prompt returned. A guest without a token gets one now.
     *
     * @param  array{phrase: string, dayTime: string, moods: list<string>, relationship: string, genres: list<string>, energy: int, popularity: int, instrumentalness: int}  $prompt
     * @param  list<string>  $songs  "Title - Artists"
     */
    public function record(array $prompt, array $songs, ?User $user): void
    {
        if ($this->isExempt($user) || $songs === []) {
            return;
        }

        PromptHistory::create([
            'user_id' => $user?->id,
            'guest_id' => $user === null ? ($this->currentGuest() ?? $this->newGuest())->id : null,
            'fingerprint' => PromptFingerprint::of($prompt),
            'prompt' => $prompt,
            'songs' => array_values($songs),
        ]);
    }

    private function isExempt(?User $user): bool
    {
        return $user?->subscribed() ?? false;
    }

    private function currentGuest(): ?Guest
    {
        $token = $this->request->cookie(self::COOKIE);

        return is_string($token) && $token !== '' ? Guest::firstWhere('token', $token) : null;
    }

    private function newGuest(): Guest
    {
        $guest = Guest::create(['token' => Str::random(64)]);

        Cookie::queue(self::COOKIE, $guest->token, self::COOKIE_MINUTES);

        return $guest;
    }
}
