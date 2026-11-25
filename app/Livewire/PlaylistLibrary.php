<?php

namespace App\Livewire;

use App\Models\Playlist;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * The playlists a player saved. They stay listed when the subscription lapses, but open again only with it.
 */
class PlaylistLibrary extends Component
{
    public function delete(int $playlistId): void
    {
        $playlist = $this->user()->playlists()->find($playlistId);

        if ($playlist === null) {
            return;
        }

        $this->authorize('delete', $playlist);
        $playlist->delete();
        unset($this->playlists);
    }

    /**
     * @return Collection<int, Playlist>
     */
    #[Computed]
    public function playlists(): Collection
    {
        return $this->user()->playlists()->latest('updated_at')->latest('id')->get();
    }

    public function render(): View
    {
        return view('livewire.playlist-library', [
            'canOpen' => $this->user()->canSavePlaylists(),
        ])->title(__('moodler.library.title').' · '.config('app.name'));
    }

    private function user(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
