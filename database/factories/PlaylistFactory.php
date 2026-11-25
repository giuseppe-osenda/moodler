<?php

namespace Database\Factories;

use App\Models\Playlist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Playlist>
 */
class PlaylistFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $phrase = fake()->sentence(6);

        return [
            'user_id' => User::factory(),
            'name' => $phrase,
            'phrase' => $phrase,
            'day_time' => 'Evening',
            'moods' => ['Relaxed'],
            'relationship' => 'Friends',
            'genres' => ['Jazz'],
            'energy' => 40,
            'popularity' => 50,
            'instrumentalness' => 20,
            'tracks' => [[
                'id' => 'track1',
                'uri' => 'spotify:track:track1',
                'url' => 'https://open.spotify.com/track/track1',
                'title' => 'So What',
                'artists' => 'Miles Davis',
                'album' => 'Kind of Blue',
                'imageUrl' => null,
                'durationMs' => 562000,
            ]],
            'custom_options' => [],
        ];
    }
}
