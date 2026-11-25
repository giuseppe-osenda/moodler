<?php

namespace App\Policies;

use App\Models\Playlist;
use App\Models\User;

/**
 * Playlists are private: only the player who saved one may open, change or delete it.
 */
class PlaylistPolicy
{
    public function view(User $user, Playlist $playlist): bool
    {
        return $playlist->user_id === $user->id;
    }

    public function update(User $user, Playlist $playlist): bool
    {
        return $playlist->user_id === $user->id;
    }

    public function delete(User $user, Playlist $playlist): bool
    {
        return $playlist->user_id === $user->id;
    }
}
